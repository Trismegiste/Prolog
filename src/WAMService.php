<?php

namespace Trismegiste\Prolog;

/**
 * WAMService is a WAM intended to use in a framework
 * with oriented service architecture
 *
 * @author Florent Genette <florent.g@allopneus.com>
 */
class WAMService extends WAM
{

    public function __construct()
    {
        parent::__construct(new Program());
    }

    protected $stringBuffer;

    protected function readLn(): string
    {
        return "";
    }

    public function write(string $s): void
    {
        $this->stringBuffer->write($s);
    }

    // displays a string followed by CRLF
    public function writeLn(string $s): void
    {
        $this->stringBuffer->writeLn($s);
    }

    /**
     * Add a pre-compiled program
     * @param Program $prog 
     * 
     * @todo Moving to PrologContext ?
     * @todo Duplicate code in consult
     * @todo refactor the loading and compiling in WAMService 
     */
    public function addProgram(Program $prog): void
    {
        $this->p->owner = $this;
        $this->p->addProgram($prog);  // add program to that already in memory
        $this->p->updateLabels();  // and don't forget to update the jump labels   
    }

    // runQuery compiles a query given by s into a WAM program, adds it to the program in memory
    // and jumps to the label "query$", starting the execution
    public function runQuery(string $s): array
    {
        $qc = new QueryCompiler($this);
        $this->reset();
        $this->p->deleteFrom("query$");
        $s = trim($s);

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

        $stackResult = [];
        $getOut = false;
        do {
            $result = new Solution();
            $this->stringBuffer = $result;
            $ms = microtime(true);
            $this->run();

            $result->elapsedTime = microtime(true) - $ms;
            $result->opCount = $this->opCount;
            $result->backtrackCount = $this->backtrackCount;

            $this->writeLn("");

            if ($this->failed) {  // if execution failed, just tell that
                $result->succeed = false;
                $this->writeLn("Failed.");
                $getOut = true;
            } else {
                $result->succeed = true;
                // if there are any query variables (e.g. in "start(X, Y)", X and Y would be such variables),
                // display their current values and ask the user if he/she wants to see more possible solutions
                if ($this->displayQCount > 0) {
                    $this->write("Success: ");
                    $cnt = 0;
                    for ($i = 0; $i < 100; $i++)  // yes, we do not allow more than 100 query variables!
                        if ($this->displayQValue[$i]) {
                            $key = $this->queryVariables[$i]->name;
                            $value = $this->queryVariables[$i];
                            $result->setQueryVar($key, $value->toArrayValue());
                            $cnt++;  // if Q[i] is to be displayed, just do that
                            $this->write($key . " = " . $value);
                            if ($cnt < $this->displayQCount)
                                $this->write(", ");
                            else
                                $this->writeLn(".");
                        }
                }
                else
                    $this->writeLn("Success.");

                // we see other choice while there is any
                if ($this->choicePoint !== null)
                    $this->backtrack();
                else
                    $getOut = true;
            }
            $stackResult[] = $result;
        } while (!$getOut);

        $this->reset();

        return $stackResult;
    }

}

