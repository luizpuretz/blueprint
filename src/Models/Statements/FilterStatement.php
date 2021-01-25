<?php


namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

class FilterStatement
{
    /**
     * @var string
     */
    private $operation;

    /**
     * @var string
     */
     private $reference;

    /**
     * @var array
     */
    private $clauses;

    /**
     * @var string
     */
    private $model = null;

    public function __construct(string $operation, string $reference, array $clauses = [])
    {
        $this->operation = $operation;
        $this->clauses = $clauses;
        $this->reference = $reference;

        if ($operation === 'usercurrent' && !empty($clauses)) {
            $this->model = Str::studly(Str::singular($clauses[0]));
        }
    }

   

    public function usercurrent()
    {
        // dd('deu aqui no usercurrent');
        return;
        // return $this->clauses;
    }


    public function output(string $controller, string $action): string
    {   

        $body = "if($".Str::lower($controller)."->".$this->reference()." != \Illuminate\Support\Facades\Auth::id()){".PHP_EOL;
        $body .= '$request->session()->flash("error", __("No permission to '.$action.' this '.Str::lower($controller).'"));'.PHP_EOL;
        $body .= 'return redirect()->route("'.Str::lower($controller).'.index");'.PHP_EOL;
        $body .= '}'.PHP_EOL;

        return $body;

    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function clauses()
    {
        return $this->clauses;
    }


    public function reference()
    {
        return $this->reference;
    }


    private function determineModel(string $prefix)
    {

        if (empty($this->model())) {
            return Str::studly(Str::singular($prefix));
        }

        return Str::studly($this->model());
    }


    
}
