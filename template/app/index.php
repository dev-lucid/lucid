<?php
# no need to load the database connection or the logger, so define constants that prevent their loading
define('__LOAD_DB__',false);
define('__LOAD_LOGGER__',false);

include(__DIR__.'/../bootstrap.php');

# load the javascript/scss configs to get the paths for the final builds
lucid::config('js');
lucid::config('scss');

?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="" />
        <meta name="author" content="" />
        <link rel="icon" href="../../favicon.ico" />
        <title>Jumbotron Template for Bootstrap</title>
        <link href="<?=str_replace(lucid::$paths['app'],'',lucid::$scss_production_build)?>" rel="stylesheet" />
    </head>
    <body onload="app.init();">
        <nav class="navbar navbar-static-top navbar-dark bg-inverse">
            <a class="navbar-brand" href="#!view.home"><?=_('branding:app_name')?></a>
            <ul class="nav navbar-nav pull-right" id="nav1"></ul>
        </nav>

        <div class="container-fluid" style="margin-top: 5px;">
            <div class="row" id="layout-two-col">
                <div class="hidden-md-down col-lg-4 col-xl-3">
                    <ul class="nav nav-pills nav-stacked nav2"></ul>
                </div>
                <div class="hidden-lg-up col-xs-12">
                    <ul class="nav nav-pills nav2"></ul>
                    <br />
                </div>
                <div class="col-xs-12 col-lg-8 col-xl-9" id="body"></div>
            </div>
            <div class="row" id="layout-full-width">
                <div class="col-xs-12" id="full-width"></div>
            </div>
        </div>
        <nav class="navbar navbar-fixed-bottom">
            <footer>
                <p>&copy; <?=_('branding::app_name')?> 2016</p>
            </footer>
        </nav>
        <script src="<?=str_replace(lucid::$paths['app'],'',lucid::$js_production_build)?>"></script>
        <script language="Javascript">
        lucid.stage = '<?=lucid::$stage?>';
        lucid.defaultRequest = '#!view.home';
        lucid.i18n.phrases['data_table:page'] = '<?=_('data_table:page')?>';
        lucid.addHandler('pre-handleResponse', function(parameters){
            var data = parameters.jqxhr.responseJSON;
            if(typeof(data.replace['#full-width']) != 'undefined' && typeof(data.replace['#body']) == 'undefined'){
                jQuery('#layout-full-width').show();
                jQuery('#layout-two-col').hide();
            }
            if(typeof(data.replace['#full-width']) == 'undefined' && typeof(data.replace['#body']) != 'undefined'){
                jQuery('#layout-full-width').hide();
                jQuery('#layout-two-col').show();
            }
        })
        </script>
    </body>
</html>
