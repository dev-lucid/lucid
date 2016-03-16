<?php

namespace DevLucid;

class ControllerCompiler extends Controller
{
    private function headers($type)
    {
        ob_clean();
        header('Content-Type: text/'.$type);
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
    }

    private function writeBuild($path, $content)
    {
        if (file_Exists($path) === true) {
            unlink($path);
        }
        file_put_contents($path, $content);
    }

    public function javascript()
    {
        lucid::config('js');

        $uncompressed = '';
        foreach (lucid::$jsFiles as $file) {
            $uncompressed .= file_get_contents($file);
        }

        # compression code goes here
        $compressed = $uncompressed;

        $this->writeBuild(lucid::$jsProductionBuild, $compressed);
    }

    public function scss()
    {
        lucid::config('scss');

        $scss = new \Leafo\ScssPhp\Compiler();
        $scss->setFormatter('Leafo\ScssPhp\Formatter\Compressed');

        foreach (lucid::$paths['scss'] as $path) {
            $scss->addImportPath($path);
        }

        $src = lucid::$scssStartSource;
        foreach (lucid::$scssFiles as $file) {
            $src .= "@import '$file';\n";
        }

        $css = $scss->compile($src);
        $this->writeBuild(lucid::$scssProductionBuild, $css);
    }

    public function documentation(string $type)
    {
        $type = trim($type);

        echo("Updating documentation for $type\n");
        $md = "<!---\nThis file is automatically generated by the lucid compiler controller. Any changes you make to this file may be overwritten.\nHowever, this file does include ".$type."_general.md, which is NOT autogenerated. Any changes you make to this file will persist.\n-->\n";
        $md .= "{{nav1 nav1_main}}\n{{nav2 nav2_mvc}}\n# ".ucwords($type)."\n{{include ".$type."_general}}\n\n";

        switch($type) {
            case 'controllers':
            case 'models':

                # Controllers and models are documented in basically the same way: locate every model/controller,
                # then use reflection to write out their parameters. In the future, use phpdoc once it
                # supports php7
                #
                if ($type == 'controllers') {
                    $path = lucid::$paths['app'].'/controllers/*.php';
                } else {
                    $path = lucid::$paths['models'].'*.php';
                }

                foreach (glob($path) as $fileName) {

                    $name = basename($fileName, '.php');
                    echo('  '.$type.': '.$name."\n");

                    # controllers and models work slightly differently. Controllers are directly instantiated by lucid::controller(),
                    # but lucid::model() does not actually return the model. Rather, it returns the idiorm class used to construct queries.
                    # once those queries are run, it returns an interator that has the actual model class inside it. So, we need to determine
                    # the actual model class, directly load the file, and then manually instantiate.
                    if ($type == 'controllers') {
                        $class = 'DevLucid\\Controller'.$name;
                        $obj = lucid::controller($name);
                    } else {
                        $class = 'DevLucid\\Model'.$name;
                        $classParts = explode('\\',$class);
                        $instantiableClass = array_pop($classParts);
                        if (class_exists($instantiableClass) === false) {
                            include($fileName);
                        }

                        $obj = new $instantiableClass();
                    }

                    $methods = get_class_methods($obj);

                    $md .= "{{card-start $name}}\n";

                    $methods_found = 0;
                    foreach ($methods as $method) {
                        $rm = new \ReflectionMethod($obj, $method);

                        # kind of awkward. Because of how the controllers are instantiated vs models, need to strip out the namepace
                        $class1 = explode('\\',strtolower($rm->class));
                        $class1 = array_pop($class1);
                        $class2 = explode('\\',strtolower($class));
                        $class2 = array_pop($class2);

                        # we only want to print methods that were defined in the final class, not its parents
                        if ($class1 == $class2) {

                            $methods_found++;
                            $md .= '**'.$rm->class.'->'.$method.'()'."**\n";

                            # print the list of parameters and optionally their type
                            $rps = $rm->getParameters();
                            foreach($rps as $rp)
                            {
                                $parameter_type = $rp->getType();
                                $parameter_type .= ' ';
                                $md .= '* '.$parameter_type.'$'.$rp->name."\n";
                            }
                            if (count($rps) === 0) {
                                $md .= "* No Parameters\n";
                            }

                            $md .= "\n";
                        }
                    }

                    if ($methods_found == 0) {
                        $md .= "No methods found.\n";
                    }

                    $md .= "{{card-end}}\n\n";
                }

                file_put_contents(lucid::$paths['base'].'/docs/'.$type.'.md', $md);
                break;

            case 'views':

                # not sure what info about views we can print yet, given that phpdoc is currently broken
                $path = lucid::$paths['app'].'/views/*.php';

                foreach (glob($path) as $fileName) {
                    echo('  '.$type.': '.basename($fileName, '.php')."\n");
                    $name = basename($fileName, '.php');
                    $md .= "{{card-start $name}}\n";

                    $md .= "{{card-end}}\n\n";
                }
                file_put_contents(lucid::$paths['base'].'/docs/'.$type.'.md', $md);

                break;
            case 'tables':
                $meta = new Metabase(\ORM::get_db());

                $to_print = [
                    [
                        'custom_include'=>'db_tables',
                        'title'=>'Tables',
                        'file'=>lucid::$paths['base'].'/docs/db_tables.md',
                        'objects'=>$meta->getTables(false)
                    ],
                    [
                        'custom_include'=>'db_views',
                        'title'=>'Views',
                        'file'=>lucid::$paths['base'].'/docs/db_views.md',
                        'objects'=>$meta->getViews()
                    ],
                ];

                foreach ($to_print as $print) {
                    $md = "<!---\nThis file is automatically generated by the lucid compiler controller. Any changes you make to this file may be overwritten.\nHowever, this file does include ".$type."_general.md, which is NOT autogenerated. Any changes you make to this file will persist.\n-->\n";
                    $md .= "{{nav1 nav1_main}}\n{{nav2 nav2_database}}\n# ".$print['title']."\n{{include ".$print['custom_include']."_general}}\n\n";

                    foreach ($print['objects'] as $object) {
                        echo('  '.$type.': '.$object."\n");
                        $columns = $meta->getColumns($object);
                        #$md .= "{{card-start ".$object."}}\n";
                        $md .= "### $object\n\n";

                        $md .= "\n\n| Column | Type |\n";
                        $md .= "| ------ | ---- |\n";
                        foreach($columns as $column) {
                            $md .= "| ".$column['type']." | ".$column['name']." |\n";
                            #$md .= "* ".$column['type']." ".$column['name']."\n";
                        }
                        $md .= "\n\n";
                        #$md .= "\n\n{{card-end}}\n";
                    }

                    file_put_contents($print['file'], $md);
                }

                break;
        }
    }
}
