<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:87:"/www/wwwroot/bill.zhoujiasong.top/public/../application/admin/view/brush_plat/edit.html";i:1605595748;s:76:"/www/wwwroot/bill.zhoujiasong.top/application/admin/view/layout/default.html";i:1588765312;s:73:"/www/wwwroot/bill.zhoujiasong.top/application/admin/view/common/meta.html";i:1588765312;s:75:"/www/wwwroot/bill.zhoujiasong.top/application/admin/view/common/script.html";i:1588765312;}*/ ?>
<!DOCTYPE html>
<html lang="<?php echo $config['language']; ?>">
    <head>
        <meta charset="utf-8">
<title><?php echo (isset($title) && ($title !== '')?$title:''); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="renderer" content="webkit">

<link rel="shortcut icon" href="/assets/img/favicon.ico" />
<!-- Loading Bootstrap -->
<link href="/assets/css/backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.css?v=<?php echo \think\Config::get('site.version'); ?>" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements. All other JS at the end of file. -->
<!--[if lt IE 9]>
  <script src="/assets/js/html5shiv.js"></script>
  <script src="/assets/js/respond.min.js"></script>
<![endif]-->
<script type="text/javascript">
    var require = {
        config:  <?php echo json_encode($config); ?>
    };
</script>
    </head>

    <body class="inside-header inside-aside <?php echo defined('IS_DIALOG') && IS_DIALOG ? 'is-dialog' : ''; ?>">
        <div id="main" role="main">
            <div class="tab-content tab-addtabs">
                <div id="content">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                            <section class="content-header hide">
                                <h1>
                                    <?php echo __('Dashboard'); ?>
                                    <small><?php echo __('Control panel'); ?></small>
                                </h1>
                            </section>
                            <?php if(!IS_DIALOG && !\think\Config::get('fastadmin.multiplenav')): ?>
                            <!-- RIBBON -->
                            <div id="ribbon">
                                <ol class="breadcrumb pull-left">
                                    <li><a href="dashboard" class="addtabsit"><i class="fa fa-dashboard"></i> <?php echo __('Dashboard'); ?></a></li>
                                </ol>
                                <ol class="breadcrumb pull-right">
                                    <?php foreach($breadcrumb as $vo): ?>
                                    <li><a href="javascript:;" data-url="<?php echo $vo['url']; ?>"><?php echo $vo['title']; ?></a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <!-- END RIBBON -->
                            <?php endif; ?>
                            <div class="content">
                                <form id="edit-form" class="form-horizontal" role="form" data-toggle="validator" method="POST" action="">

    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Account'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-account" class="form-control" name="row[account]" type="text" value="<?php echo htmlentities($row['account']); ?>">-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Recive'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-recive" class="form-control" name="row[recive]" type="text" value="<?php echo htmlentities($row['recive']); ?>">-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Mobile'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-mobile" class="form-control" name="row[mobile]" type="text" value="<?php echo htmlentities($row['mobile']); ?>">-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Gender'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
                        
    <!--        <select  id="c-gender" class="form-control selectpicker" name="row[gender]">-->
    <!--            <?php if(is_array($genderList) || $genderList instanceof \think\Collection || $genderList instanceof \think\Paginator): if( count($genderList)==0 ) : echo "" ;else: foreach($genderList as $key=>$vo): ?>-->
    <!--                <option value="<?php echo $key; ?>" <?php if(in_array(($key), is_array($row['gender'])?$row['gender']:explode(',',$row['gender']))): ?>selected<?php endif; ?>><?php echo $vo; ?></option>-->
    <!--            <?php endforeach; endif; else: echo "" ;endif; ?>-->
    <!--        </select>-->

    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Recive_city'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <div class='control-relative'><input id="c-recive_city" class="form-control" data-toggle="city-picker" name="row[recive_city]" type="text" value="<?php echo htmlentities($row['recive_city']); ?>"></div>-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Recive_address'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-recive_address" class="form-control" name="row[recive_address]" type="text" value="<?php echo htmlentities($row['recive_address']); ?>">-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('My_image'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <div class="input-group">-->
    <!--            <input id="c-my_image" class="form-control" size="50" name="row[my_image]" type="text" value="<?php echo htmlentities($row['my_image']); ?>">-->
    <!--            <div class="input-group-addon no-border no-padding">-->
    <!--                <span><button type="button" id="plupload-my_image" class="btn btn-danger plupload" data-input-id="c-my_image" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="false" data-preview-id="p-my_image"><i class="fa fa-upload"></i> <?php echo __('Upload'); ?></button></span>-->
    <!--                <span><button type="button" id="fachoose-my_image" class="btn btn-primary fachoose" data-input-id="c-my_image" data-mimetype="image/*" data-multiple="false"><i class="fa fa-list"></i> <?php echo __('Choose'); ?></button></span>-->
    <!--            </div>-->
    <!--            <span class="msg-box n-right" for="c-my_image"></span>-->
    <!--        </div>-->
    <!--        <ul class="row list-inline plupload-preview" id="p-my_image"></ul>-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Myinfo_image'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <div class="input-group">-->
    <!--            <input id="c-myinfo_image" class="form-control" size="50" name="row[myinfo_image]" type="text" value="<?php echo htmlentities($row['myinfo_image']); ?>">-->
    <!--            <div class="input-group-addon no-border no-padding">-->
    <!--                <span><button type="button" id="plupload-myinfo_image" class="btn btn-danger plupload" data-input-id="c-myinfo_image" data-mimetype="image/gif,image/jpeg,image/png,image/jpg,image/bmp" data-multiple="false" data-preview-id="p-myinfo_image"><i class="fa fa-upload"></i> <?php echo __('Upload'); ?></button></span>-->
    <!--                <span><button type="button" id="fachoose-myinfo_image" class="btn btn-primary fachoose" data-input-id="c-myinfo_image" data-mimetype="image/*" data-multiple="false"><i class="fa fa-list"></i> <?php echo __('Choose'); ?></button></span>-->
    <!--            </div>-->
    <!--            <span class="msg-box n-right" for="c-myinfo_image"></span>-->
    <!--        </div>-->
    <!--        <ul class="row list-inline plupload-preview" id="p-myinfo_image"></ul>-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Ctime'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-ctime" class="form-control datetimepicker" data-date-format="YYYY-MM-DD HH:mm:ss" data-use-current="true" name="row[ctime]" type="text" value="<?php echo $row['ctime']?datetime($row['ctime']):''; ?>">-->
    <!--    </div>-->
    <!--</div>-->
    
    <div class="form-group">
        <label class="control-label col-xs-12 col-sm-2"><?php echo __('Status'); ?>:</label>
        <div class="col-xs-12 col-sm-8">
            
            <div class="radio">
            <?php if(is_array($statusList) || $statusList instanceof \think\Collection || $statusList instanceof \think\Paginator): if( count($statusList)==0 ) : echo "" ;else: foreach($statusList as $key=>$vo): ?>
            <label for="row[status]-<?php echo $key; ?>"><input id="row[status]-<?php echo $key; ?>" name="row[status]" type="radio" value="<?php echo $key; ?>" <?php if(in_array(($key), is_array($row['status'])?$row['status']:explode(',',$row['status']))): ?>checked<?php endif; ?> /> <?php echo $vo; ?></label> 
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>

        </div>
    </div>
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Plat_id'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-plat_id" data-rule="required" data-source="plat/index" class="form-control selectpage" name="row[plat_id]" type="text" value="<?php echo htmlentities($row['plat_id']); ?>">-->
    <!--    </div>-->
    <!--</div>-->
    <!--<div class="form-group">-->
    <!--    <label class="control-label col-xs-12 col-sm-2"><?php echo __('Brush_id'); ?>:</label>-->
    <!--    <div class="col-xs-12 col-sm-8">-->
    <!--        <input id="c-brush_id" data-rule="required" data-source="brush/index" class="form-control selectpage" name="row[brush_id]" type="text" value="<?php echo htmlentities($row['brush_id']); ?>">-->
    <!--    </div>-->
    <!--</div>-->
    <div class="form-group layer-footer">
        <label class="control-label col-xs-12 col-sm-2"></label>
        <div class="col-xs-12 col-sm-8">
            <button type="submit" class="btn btn-success btn-embossed disabled"><?php echo __('OK'); ?></button>
            <button type="reset" class="btn btn-default btn-embossed"><?php echo __('Reset'); ?></button>
        </div>
    </div>
</form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="/assets/js/require<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js" data-main="/assets/js/require-backend<?php echo \think\Config::get('app_debug')?'':'.min'; ?>.js?v=<?php echo htmlentities($site['version']); ?>"></script>
    </body>
</html>