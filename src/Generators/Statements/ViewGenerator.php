<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Tree;
use Illuminate\Support\Str;

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

        // dd($tree->controllers());
        // $treeConstrollers[] = "";
        foreach ($tree->controllers() as $k => $c) {
            $treeConstrollers[] =  Str::lower($c->name());
        }
        // dd($treeConstrollers);
        
        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            // dd($controller);
            foreach ($controller->methods() as $method => $statements) {
                
                // dd($tree->models()[$controller->name()]->columns());
                

                if(in_array($method, array('index', 'create', 'show', 'edit'))){
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

                        // dd($treeConstrollers);

                        $this->files->put($path, $this->populateStubIndex($stub, $tree->models()[$controller->name()]->columns(), $controller, $statement->data()['0'], $treeConstrollers));
                    
                    }else if($method == 'create'){

                        // {{ routecreate }}

                        $this->files->put($path, $this->populateStubCreate($stub, $tree->models()[$controller->name()]->columns(), $controller, ""));
                    
                    }else if($method == 'edit'){

                    // {{ routecreate }}

                    $this->files->put($path, $this->populateStubEdit($stub, $tree->models()[$controller->name()]->columns(), $controller, "", true));

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

    protected function generateColumn($name, $type, $attr, $controlername, $edit = false){


        $class = "block w-full px-4 py-4 mb-3 leading-tight text-gray-700 bg-gray-200 border border-gray-200 rounded appearance-none focus:outline-none focus:bg-white focus:border-gray-500";
        $isedit =  "";
        $selectedit = "";
        if($edit){
            $isedit = '?? $'.$controlername.'->'.$name;
            $selectedit = '$'.$controlername.'->'.$name;
            // dd($name, $type, $attr, $controlername, $edit, $isedit);
        }


        if($type == 'id'){
            if($name == 'user_id'){
                return '<input type="hidden" name="'.$name.'" value="{{ \Illuminate\Support\Facades\Auth::id() }}">'.PHP_EOL;
            }else{
                return '<input type="hidden" name="'.$name.'">'.PHP_EOL;
            }
    
        }else if($type == 'string'){
        
            return '<input type="text" name="'.$name.'" value="{{ old(\''.$name.'\') '.$isedit.' }}" placeholder="'.$name.'" class="'.$class.'">'.PHP_EOL;
        
        }else if($type == 'dateTime'){
        
        return '<input type="datetime-local" name="'.$name.'" value="{{ old(\''.$name.'\') '.$isedit.'->format(\'Y-m-d\TH:i:s\') }}"  placeholder="'.$name.'" class="'.$class.'">'.PHP_EOL;

        }else if($type == 'timestamp'){
        
            return '<input type="date" name="'.$name.'" value="{{ old(\''.$name.'\') '.$isedit.' }}"  placeholder="'.$name.'" class="'.$class.'">'.PHP_EOL;
    
        }else if($type == 'longText'){
            
            return '<textarea name="'.$name.'" placeholder="'.$name.'" rows="4" cols="50" class="'.$class.'">{{ old(\''.$name.'\') '.$isedit.' }}</textarea>'.PHP_EOL;

        }else if($type == 'integer'){
            
            return '<input type="number" name="'.$name.'" value="{{ old(\''.$name.'\') '.$isedit.' }}"  placeholder="'.$name.'" class="'.$class.'">'.PHP_EOL;

        }else if($type == 'enum'){

            $body = '<select name="'.$name.'" class="'.$class.'">'.PHP_EOL;
                $body .= '<option>'.$name.'</option>'.PHP_EOL;
                foreach ($attr as $k => $v) {

                    if($edit){
                        $body .= '<option value="'.$v.'" {{ (old(\''.$name.'\') ?? '. $selectedit.') == "'.$v.'" ? "selected" : "" }}>'.$v.'</option>'.PHP_EOL;
                    }else{
                        $body .= '<option value="'.$v.'" {{ old(\''.$name.'\') == "'.$v.'" ? "selected" : "" }}>'.$v.'</option>'.PHP_EOL;
                    }
                }
                
            $body .= '</select>'.PHP_EOL;
            return $body;

        }else{
            return $name." - ".$type;
        }
        
    }


    protected function stubRouteCreate($stub, $controller)
    {
        return str_replace('{{ routecreate }}', Str::lower($controller->name()).'.store', $stub);
    }


    protected function stubRouteEdit($stub, $controller)
    {
        // return str_replace('routeedit', Str::lower($controller->name()).'.update', $stub);
        return str_replace('{{ routeedit }}', "route('".Str::lower($controller->name()).".update"."', $".Str::lower($controller->name())."->id)", $stub);
        // route('post.update', $post->id)
    }


    protected function populateStubEdit(string $stub, $columns, $controller, $list = "", $edit = false)
    {

        $controlerSimple = strtolower($controller->name());

        $stub =  $this->stubTitlePage($stub, $controller);

        $stub =  $this->stubRouteEdit($stub, $controller);

        // dd($controller);

        foreach ($columns as $key => $value) {

        //    dd($columns);
            
            // $list .= $key." - ".$value->dataType().PHP_EOL;
            $list .= $this->generateColumn($key, $value->dataType(), $value->attributes(), Str::lower($controller->name()), $edit).PHP_EOL;

        }

        return str_replace('{{ view }}', $list, $stub);



    }


    protected function populateStubCreate(string $stub, $columns, $controller, $list = "", $edit = false)
    {

        $controlerSimple = strtolower($controller->name());

        $stub =  $this->stubTitlePage($stub, $controller);

        $stub =  $this->stubRouteCreate($stub, $controller);

        // dd($controller);

        foreach ($columns as $key => $value) {

        //    dd($columns);
            
            // $list .= $key." - ".$value->dataType().PHP_EOL;
            $list .= $this->generateColumn($key, $value->dataType(), $value->attributes(), Str::lower($controller->name()), $edit).PHP_EOL;

        }

        return str_replace('{{ view }}', $list, $stub);



    }

    protected function populateStubIndex(string $stub, $columns, $controller, $statData, $treeConstrollers = [])
    {
        $list = "";
        // dd($treeConstrollers);
        $aux = 0;
        $controlerSimple = strtolower($controller->name());
        // $list = '@foreach ($'.$statData." as ".$controlerSimple.')';
        // {{ route('post.show', $post->id) }}

        $stub = $this->stubSelectController($stub, $treeConstrollers);

        $stub =  $this->stubTitlePage($stub, $controller);

        $list .= "<ul>\n";
        $list .= '                    @foreach ($'.$statData." as $".$controlerSimple.")\n";
        
        foreach ($columns as $key => $value) {
            if($aux == 0 && $value->dataType() == 'string'){
                
                $list .= '                        <li class="flex mb-3">'.PHP_EOL;
                $list .='<form class="flex-initial" action="{{ route(\''.$controlerSimple.'.destroy\', $'.$controlerSimple.'->id) }}" method="POST">
                            @method(\'DELETE\')
                            @csrf
                            <button class="px-4 py-2 font-bold text-white bg-red-500 rounded hover:bg-red-700">Delete '.$controlerSimple.'</button>
                        </form>'.PHP_EOL;


                $list .='<a class="px-4 py-2 ml-1 font-bold text-white bg-blue-500 rounded hover:bg-blue-700" href="{{ route(\''.$controlerSimple.'.edit\', $'.$controlerSimple.'->id) }}">Editar</a>'.PHP_EOL;



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

    protected function stubSelectController($stub, $treeConstrollers)
    {

        $select = '<select  onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);" class="px-3 py-2 bg-white border rounded outline-none">'.PHP_EOL;
        foreach ($treeConstrollers as $key => $value) {
            $select .= '<option value="/'.$value.'" '."{{ request()->is('".$value."')?'selected':'' }}".'>Page '.Str::ucfirst($value).'</option>'.PHP_EOL;
        }
        $select .= "</select>".PHP_EOL;
        
        return str_replace('{{ selecteforredirect }}',$select, $stub);
    }


}
