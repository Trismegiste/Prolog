<?php

namespace Trismegiste\Prolog;

/**
 * Warren's Abstract Machine  -  Implementation by Stefan Buettcher
 * developed:   December 2001 until February 2002
 *
 * Translated from Java to PHP 5.3 by Florent Genette (Trismegiste)
 * http://github.com/Trismegiste/Wam
 * June to July 2012 (yes, 10 years after the original version !)
 *
 * WAMConsole is a WAM for a CLI application
 */
class WAMConsole extends WAM
{

    protected function readLn()
    {
        try {
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);

            return trim($line);
        } catch (\Exception $e) {

            return "";
        }
    }

    public function write($s)
    {
        echo $s;
    }

    // displays a string followed by CRLF
    public function writeLn($s)
    {
        echo $s . "\n";
    }

    // showHelp shows a list of the available commands
    protected function showHelp()
    {
        $this->writeLn("This is Stu's mighty WAM speaking. Need some help?");
        $this->writeLn("");
        $this->writeLn("Available commands:");
        $this->writeLn("exit                    terminates the WAM");
        $this->writeLn("help                    displays this help");
        $this->writeLn("list                    lists the WAM program currently in memory");
        $this->writeLn("new                     removes all WAM code from memory");
        $this->writeLn("set [PARAM[=VALUE]]     displays all internal parameters (\"set\") or lets");
        $this->writeLn("                        the user set a parameter's new value, respectively");
        $this->writeLn("labels                  displays all labels that can be found in memory");
        $this->writeLn("procedures              displays the names of all procedures in memory");
        $this->writeLn("quit                    terminates the WAM");
        $this->writeLn("");
        $this->writeLn("Prolog programs can be compiled into memory by typing \"consult(filename).\",");
        $this->writeLn("e.g. \"consult('lists.pro').\". Existing WAM programs can be loaded into");
        $this->writeLn("memory by typing \"load(filename).\".");
        $this->writeLn("");
        $this->writeLn("" . $this->p->getStatementCount() . " lines of code in memory.");
    }

    // runQuery compiles a query given by s into a WAM program, adds it to the program in memory
    // and jumps to the label "query$", starting the execution
    public function runQuery($s)
    {
        $qc = new QueryCompiler($this);
        $this->reset();
        $this->p->deleteFrom("query$");
        $s = trim($s);

        /*         * ************* BEGIN SPECIAL COMMANDS ************** */

        // input "quit" or "exit" means: end the WAM now, dude!
        if (in_array($s, array("quit", "exit")))
            return false;
        if ($s == "help") {
            $this->showHelp();  // display some help information
            return true;
        }
        if ($s == "set") {
            $this->displayInternalVariables();  // show the states of the internal parameters
            return true;
        }
        if ($s == "labels") {  // show all labels of the current program
            for ($i = 0; $i < $this->p->getStatementCount(); $i++) {
                $m = $this->p->getStatement($i)->getLabel();
                if (strlen($m) > 0)
                    $this->writeLn($m);
            }
            return true;
        }
        if ($s == "procedures") {  // show all procedure names of the current program
            for ($i = 0; $i < $this->p->getStatementCount(); $i++) {
                $m = $this->p->getStatement($i)->getLabel();
                if ((strlen($m) > 0) && (false === strpos($m, '~')))
                    $this->writeLn($m);
            }
            return true;
        }
        if ($s == "list") {  // show the WAM code of the program currently in memory
            if ($this->p->getStatementCount() == 0)
                $this->writeLn("No program in memory.");
            else
                $this->writeLn($this->p->__toString());
            return true;
        }
        if ($s == "new") {  // clear memory
            $this->p = new Program($this);
            $this->writeLn("Memory cleared.");
            return true;
        }
        if ((strlen($s) > 4) && (substr($s, 0, 4) == "set ")) {
            if (preg_match('#^set\s+([A-Za-z]+)=(.+)$#', $s, $match)) {
                $this->setInternalVariable($match[1], $match[2]);
            } elseif (preg_match('#^set\s+([A-Za-z]+)\s*$#', $s, $match)) {
                $this->getInternalVariable($match[1]);
            }
            return true;
        } // end of "set ..." command

        /*         * ************* END SPECIAL COMMANDS ************** */

        $query = $qc->compile($s);

        if ($query == null) {  // query could not be compiled
            $this->writeLn("Illegal query.");
            return true;
        } else {
            if ($this->debugOn > 1) {  // if in debug mode, display query WAM code
                $this->writeLn("----- BEGIN QUERYCODE -----");
                $this->writeLn($query->__toString());
                $this->writeLn("------ END QUERYCODE ------");
            }
            $this->p->addProgram($query);  // add query to program in memory and
            $this->p->updateLabels();  // update the labels for jumping hin und her
        }

        // reset the WAM's registers and jump to label "query$" (the current query, of course)
        $this->programCounter = $this->p->getLabelIndex("query$");
        $answer = "";
        do {
            $ms = microtime(true);
            $this->run();

            if ($this->benchmarkOn > 0)  // sometimes, we need extra benchmark information
                $this->writeLn("Total time elapsed: " + (microtime(true) - $ms) + " ms.");
            $this->writeLn("");

            if ($this->failed) {  // if execution failed, just tell that
                $this->writeLn("Failed.");
                break;
            }

            // if there are any query variables (e.g. in "start(X, Y)", X and Y would be such variables),
            // display their current values and ask the user if he/she wants to see more possible solutions
            if ($this->displayQCount > 0) {
                $this->write("Success: ");
                $cnt = 0;
                for ($i = 0; $i < 100; $i++)  // yes, we do not allow more than 100 query variables!
                    if ($this->displayQValue[$i]) {
                        $cnt++;  // if Q[i] is to be displayed, just do that
                        $this->write($this->queryVariables[$i]->name . " = ");
                        $this->write($this->queryVariables[$i]->__toString());
                        if ($cnt < $this->displayQCount)
                            $this->write(", ");
                        else
                            $this->writeLn(".");
                    }
            }
            else
                $this->writeLn("Success.");
            // if there are any more choicepoints left, ask the user if they shall be tried
            if ($this->choicePoint !== null) {

                $this->write("More? ([y]es/[n]o) ");
                $answer = $this->readLn();
                $this->writeLn("");
            }
            else
                break;

            // if the users decided to see more, show him/her. otherwise: terminate
            if (($answer == "y") || ($answer == "yes"))
                $this->backtrack();
        } while (($answer == "y") || ($answer == "yes"));
        $this->reset();
        return true;
    }

    // the WAM's main loop
    public static function main(array $args)
    {
        echo"\nWelcome to Stu's mighty WAM!\n";
        echo "(December 2001 - February 2002 by Stefan Buettcher)\n";
        echo "(July 2012 - ported to PHP by Florent Genette)\n";
        echo "Type \"help\" to get some help.\n";
        $wam = new WAMConsole(new Program());
        $wam->p->owner = $wam;
        $s = '';
        do {
            $wam->writeLn("");
            $wam->write("QUERY > ");
            $s = $wam->readLn();
            $wam->writeLn("");
        } while (($s != null) && ($wam->runQuery($s)));
        $wam->writeLn("Goodbye!");
        $wam->writeLn("");
    }

// end of WAM.main(String[])
}
