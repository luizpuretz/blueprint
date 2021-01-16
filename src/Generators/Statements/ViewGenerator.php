<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Tree;

class ViewGenerator implements Generator
{
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $output = [];

        // $stub = $this->files->stub('view.stub');
        
        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            // dd($controller);
            foreach ($controller->methods() as $method => $statements) {
                
                // dd($tree->models()[$controller->name()]->columns());
                

                if(in_array($method, array('index', 'create', 'show'))){
                    $stub = $this->files->stub("view_".$method.".stub");
                }else{
                    $stub = $this->files->stub('view.stub');
                }

                foreach ($statements as $statement) {
                    
                    if (! $statement instanceof RenderStatement) {
                        continue;
                    }

                    $path = $this->getPath($statement->view());

                    if ($this->files->exists($path)) {
                        // TODO: mark skipped...
                        continue;
                    }

                    if (! $this->files->exists(dirname($path))) {
                        $this->files->makeDirectory(dirname($path), 0755, true);
                    }

                    // dd($tree->models()[$controller->name()]->columns());
                        // dd($controller);
                        // dd($tree);
                        // dd($statement->data()['0']);

                    if($method == 'index'){
                        // dd('xx');
                        $this->files->put($path, $this->populateStubIndex($stub, $tree->models()[$controller->name()]->columns(), $controller, $statement->data()['0']));
                    
                    }else if($method == 'show'){
                        // dd('xx');
                        $this->files->put($path, $this->populateStubShow($stub, $tree->models()[$controller->name()]->columns(), $controller));
                    }else{
                        // dd('yy');
                        $this->files->put($path, $this->populateStub($stub, $statement, $controller));
                    }

                    $output['created'][] = $path;
                }
            }
        }

        return $output;
    }

    public function types(): array
    {
        return ['controllers', 'views'];
    }

    protected function getPath(string $view)
    {
        return 'resources/views/'.str_replace('.', '/', $view).'.blade.php';
    }


    protected function populateStubIndex(string $stub, $columns, $controller, $statData, $list = "")
    {
        $aux = 0;
        $controlerSimple = strtolower($controller->name());
        // $list = '@foreach ($'.$statData." as ".$controlerSimple.')';
        // {{ route('post.show', $post->id) }}

        $stub =  $this->stubTitlePage($stub, $controller);

        $list .= "<ul>\n";
        $list .= '                    @foreach ($'.$statData." as $".$controlerSimple.")\n";
        
        foreach ($columns as $key => $value) {
            if($aux == 0 && $value->dataType() == 'string'){
                
                $list .= '                        <li class="flex mb-3">'."\n";
                $list .='<form class="flex-initial" action="{{ route(\''.$controlerSimple.'.destroy\', $'.$controlerSimple.'->id) }}" method="POST">
                            @method(\'DELETE\')
                            @csrf
                            <button class="px-4 py-2 font-bold text-white bg-red-500 rounded hover:bg-red-700">Delete '.$controlerSimple.'</button>
                        </form>';



                $list .= '                            <a class="flex-initial pt-2 ml-2 hover:text-gray-400" href="{{ route("'.$controlerSimple.'.show",$'.$controlerSimple.'->id) }}">{{ $'.$controlerSimple.'->'.$value->name()." }}</a>\n";

                $list .= "                        </li>\n";

                $aux = 1;

            }
        
        }

        $list .= "                    @endforeach\n";
        $list .= "                </ul>";

        return str_replace('{{ view }}', $list, $stub);
    }


    protected function populateStubShow(string $stub, $columns, $controller, $list = "")
    {
        foreach ($columns as $key => $value) {
           $list .= "                <li>".$key.": {{ $".strtolower($controller->name())."->".$key." }}</li>\n";
        }

        return str_replace('{{ view }}', $list, $this->stubTitlePage($stub, $controller));
    }

    protected function populateStub(string $stub, RenderStatement $renderStatement, $controller)
    {
        return str_replace('{{ view }}', $renderStatement->view(), $this->stubTitlePage($stub, $controller));
    }

    protected function stubTitlePage($stub, $controller)
    {
        return str_replace('{{ title_page }}', $controller->name(), $stub);
    }


}
