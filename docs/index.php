<?php
global $parser, $content;
include(__DIR__.'/../../../../bootstrap.php');

$parser = new Parsedown();
$page = (isset($_REQUEST['page']))?$_REQUEST['page']:'start';
$content = [
    'nav1'=>'',
    'nav2'=>'',
    'page'=>'',
];
$content['page'] = build_doc($page);

function build_doc($page)
{
    global $parser, $content;
    $paths = [__DIR__.'/../../../../docs', __DIR__.'/../docs', ];
    $replaces = [
        '{{card-end}}'=>'</div></div>',
    ];

    $final_file_path = null;
    foreach($paths as $path)
    {
        $test_path = $path.'/'.$page.'.md';
        if(file_exists($test_path) && is_null($final_file_path) === true)
        {
            $final_file_path = $test_path;
        }
    }

    if(is_null($final_file_path) === true)
    {
        throw new Exception('Could not find page: '.$page);
    }

    $html = $parser->text(file_get_contents($final_file_path));

    foreach($replaces as $key=>$value)
    {
        $html = str_replace($key, $value, $html);
    }

    preg_match_all('/{{([^\\s\\\\]+) (.+?)}}/', $html, $matches);
    for($i=0; $i<count($matches[1]); $i++)
    {
        $action = $matches[1][$i];
        $value  = $matches[2][$i];
        switch($action)
        {
            case 'warning':
            case 'info':
            case 'danger':
                $html = str_replace('{{'.$action.' '.$value.'}}', '<div class="alert alert-'.$action.'"><strong>'.ucwords($action).': </strong> '.$value.'</div>', $html);
                break;
            case 'card-start':
                $html = str_replace('{{'.$action.' '.$value.'}}', '<div class="card"><div class="card-header">'.$value.'</div><div class="card-block">', $html);
                break;
            case 'nav1':
            case 'nav2':
                $html = str_replace('{{'.$action.' '.$value.'}}', '', $html);
                $content[$action] = build_doc($value);
                break;
            case 'include':
                $html = str_replace('{{'.$action.' '.$value.'}}', build_doc($value), $html);
                break;
        }
    }
    return $html;
}

?><html>
    <head>
        <title>Documentation</title>
        <style type="text/css">
        <?=file_get_contents(__DIR__.'/../../../twbs/bootstrap/dist/css/bootstrap.css')?>

        pre{
            background-color: #f9f9f9;
            border: #ddd 1px solid;
            border-width: 1px 1px 1px 3px;
            border-color: #e1e1e1 #e1e1e1 #e1e1e1 #bbb;
            padding: 6px 8px;
        }
        </style>
    </head>
    <body style="padding-top:62px;">
        <?=$content['nav1']?>
        <div class="container-fluid">
            <div class="col-xs-12 col-sm-12 col-md-4 col-lg-3 col-xl-2">
                <?=$content['nav2']?>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-8 col-lg-9 col-xl-10">
                <?=$content['page']?>
            </div>
        </div>
    </body>
</html>