<?php


include 'Parsing/HtmlParser.php';

use Keiwen\Utils\Parsing\HtmlParser;

function queryCurl($url) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $content = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $ermsg = curl_error($ch);
    curl_close($ch);

    if($httpCode >= 400) {
        $error = "HTTP code $httpCode";
        $content = '';
    } else if($errno > 0) {
        $error = "Curl error $ermsg";
        $content = '';
    }
    $content = trim($content);

    if(!empty($error)) {
        ?>
        <div class="alert alert-danger" role="alert">
            <?= $error; ?>
        </div>
        <?php
    }

    return $content;
}


if(empty($_POST['steps'])) $_POST['steps'] = array();
$steps = empty($_POST['steps']) ? array() : $_POST['steps'];

$url = empty($_POST['url']) ? '' : $_POST['url'];
$rawFile = empty($_FILES['rawFile']['tmp_name']) ? array() : $_FILES['rawFile']['tmp_name'];
$content = '';
$parsed = '';

if(!empty($url) || !empty($rawFile)) {
    if(empty($url)) {
        $content = file_get_contents($rawFile);
    } else {
        $content = queryCurl($url);
    }

    $parsed = $content;
    foreach($steps as $step) {
        if(empty($parsed)) break;
        switch($step['type']) {
            case 'id':
                if(is_array($parsed)) {
                    foreach($parsed as &$part) {
                        $part = (new HtmlParser($part))->parseHtmlElmt($step['value']);
                    }
                } else {
                    $parsed = (new HtmlParser($parsed))->parseHtmlElmt($step['value']);
                }
                break;
            case 'class':
                if(is_array($parsed)) {
                    foreach($parsed as &$part) {
                        $part = (new HtmlParser($part))->parseHtmlElmt($step['value'], false, $step['iteration']);
                    }
                } else {
                    $parsed = (new HtmlParser($parsed))->parseHtmlElmt($step['value'], false, $step['iteration']);
                }
                break;
            case 'tag':
                if(is_array($parsed)) {
                    foreach($parsed as &$part) {
                        $part = (new HtmlParser($part))->parseTag($step['value'], false, $step['iteration']);
                    }
                } else {
                    $parsed = (new HtmlParser($parsed))->parseTag($step['value'], false, $step['iteration']);
                }
                break;
            case 'taglist':
                if(is_array($parsed)) {
                    $error = 'More than one list tag is not supported';
                } else {
                    $parsed = (new HtmlParser($parsed))->parseTagList($step['value']);
                }
                break;
            case 'tagattr':
                if(is_array($parsed)) {
                    foreach($parsed as &$part) {
                        $part = (new HtmlParser($part))->parseTagAttribute($step['value']);
                    }
                } else {
                    $parsed = (new HtmlParser($parsed))->parseTagAttribute($step['value']);
                }
                break;
        }
    }
    if(is_array($parsed)) $parsed = implode(PHP_EOL.PHP_EOL.'-----'.PHP_EOL.PHP_EOL.PHP_EOL, $parsed);

}


?>


<html lang="en">
<head>
    <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">

</head>
<body>
<div class="container-fluid">
    <div class="page-header">
        <h1><?= empty($parserTitle) ? 'HTML Parser' : $parserTitle ?></h1>
    </div>

    <div class="col-sm-4">

        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Parsing steps</h3>
            </div>
            <div class="panel-body">
                <form method="post" enctype="multipart/form-data" >
                    <div class="form-group row">
                        <label class="col-form-label col-xs-2">Url:</label>
                        <div class="col-xs-10">
                            <input name="url" type="text" class="form-control" value="<?= $url; ?>"/>
                        </div>
                    </div>
                    <div class="row" style="text-align: center">
                        or add file (but add it before every parse)
                    </div>
                    <div class="form-group row">
                        <label class="col-form-label col-xs-2">File:</label>
                        <div class="col-xs-10">
                            <input name="rawFile" type="file" class="form-control" />
                        </div>
                    </div>

                    <hr/>
                    <div id="parseSteps">

                    </div>
                    <hr/>

                    <button class="btn btn-danger form-control">Parse</button>

                </form>
            </div>
        </div>

        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Add step</h3>
            </div>
            <div class="panel-body">
                <div class="col-xs-10">
                    <select id="stepAdder" class="form-control">
                        <option value="id">Html id</option>
                        <option value="class">Html class</option>
                        <option value="taglist">Tag list</option>
                        <option value="tag">Tag</option>
                        <option value="tagattr">Tag attribute</option>
                    </select>
                </div>
                <div class="col-xs-2">
                    <button class="btn btn-info" id="stepValid">Add</button>
                </div>
            </div>
        </div>

    </div>


    <div class="col-sm-8">
        <div class="panel panel-warning">
            <div class="panel-heading" data-toggle="collapse" data-target="#initialContainer">
                <h3 class="panel-title">Initial</h3>
            </div>
            <div class="panel-body collapse" id="initialContainer">
                <pre><?= htmlspecialchars($content, ENT_IGNORE); ?></pre>
            </div>
        </div>

        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">Parsing result</h3>
            </div>
            <div class="panel-body">
                <pre><?= htmlspecialchars($parsed, ENT_IGNORE); ?></pre>
            </div>
        </div>

    </div>




    <div id="templates" class="hidden">
        <div id="common_template">
            <span class="col-xs-1"><i class="fa fa-minus-circle text-danger stepremove" style="cursor: pointer;"></i></span>
            <span class="col-xs-1 stepnum"></span>
        </div>
        <div id="template_id" class="form-group row">
            <label class="col-form-label col-xs-2">Id:</label>
            <div class="col-xs-8">
                <input name="value" type="text" class="form-control"/>
                <input name="type" type="hidden" value="id"/>
            </div>
        </div>
        <div id="template_class" class="form-group row">
            <label class="col-form-label col-xs-2">Class:</label>
            <div class="col-xs-4">
                <input name="value" type="text" class="form-control"/>
                <input name="type" type="hidden" value="class"/>
            </div>
            <label class="col-form-label col-xs-1">Iter.:</label>
            <div class="col-xs-3">
                <input name="iteration" type="number" value="1" min="1" class="form-control"/>
            </div>
        </div>
        <div id="template_taglist" class="form-group row">
            <label class="col-form-label col-xs-2">Tag list:</label>
            <div class="col-xs-8">
                <input name="value" type="text" class="form-control"/>
                <input name="type" type="hidden" value="taglist"/>
            </div>
        </div>
        <div id="template_tag" class="form-group row">
            <label class="col-form-label col-xs-2">Tag:</label>
            <div class="col-xs-4">
                <input name="value" type="text" class="form-control"/>
                <input name="type" type="hidden" value="tag"/>
            </div>
            <label class="col-form-label col-xs-1">Iter.:</label>
            <div class="col-xs-3">
                <input name="iteration" type="number" value="1" min="1" class="form-control"/>
            </div>
        </div>
        <div id="template_tagattr" class="form-group row">
            <label class="col-form-label col-xs-2">Attribute:</label>
            <div class="col-xs-8">
                <input name="value" type="text" class="form-control"/>
                <input name="type" type="hidden" value="tagattr"/>
            </div>
        </div>
    </div>

</div>
</body>


<script>
    var stepIndex = 0;
    var initSteps = $.parseJSON('<?= json_encode($steps); ?>');

    $.each(initSteps, function(index, step) {
        addStep(step.type, step.value, step.iteration);
    });

    $('#stepValid').click(function() {
        addStep($('#stepAdder').val());
    });

    function addStep(type, value, iteration) {
        stepIndex++;
        console.log('adding ' + stepIndex);
        if(typeof value == 'undefined') value = '';
        if(typeof iteration == 'undefined') iteration = 1;
        var template = $('#template_'+type).clone();
        template.html($('#common_template').html() + template.html());
        var inputs = template.find('input');
        $.each(inputs, function(index, input) {
            var inputName = $(this).attr('name');
            if(inputName == 'value') $(this).attr('value', value);
            if(inputName == 'iteration') $(this).attr('value', iteration);
            $(this).attr('name', 'steps['+stepIndex+']['+inputName+']');
        });
        var span = template.find('span.stepnum');
        span.html(stepIndex);
        var remover = template.find('i.stepremove');
        remover.attr('id', 'stepremove'+stepIndex);
        remover.click(function() {
            removeStep($(this));
        });
        template.attr('id', 'step'+stepIndex);
        template.addClass('stepgroup');
        $('#parseSteps').append(template);
    }


    function removeStep(elmt) {
        var parent = elmt.parents('.stepgroup');
        var removeIndex = parent.attr('id').replace('step', '');
        console.log('removing ' + removeIndex);
        parent.remove();
        for(var i = removeIndex; i < stepIndex; ) {
            i++;
            var group = $('#step'+i);
            var span = group.find('span.stepnum');
            span.html(i-1);
            var inputs = group.find('input');
            $.each(inputs, function(index, input) {
                var inputName = $(this).attr('name');
                inputName = inputName.replace('steps['+(i)+']', 'steps['+(i-1)+']');
                $(this).attr('name', inputName);
            });
            group.attr('id', 'step'+(i-1));
        }
        stepIndex--;
        console.log('removed ' + stepIndex);
    }

</script>

