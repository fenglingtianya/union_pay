<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Elements | Amanda Admin Template</title>
        <link type="text/css" href="/static/amanda/css/style.default.css" rel="stylesheet"></link>
        <script type="text/javascript" src="/static/amanda/js/plugins/jquery-1.7.min.js"></script>
        <script type="text/javascript" src="/static/amanda/js/plugins/jquery.alerts.js"></script>
        <script type="text/javascript" src="/static/amanda/js/plugins/jquery.alerts.js"></script>
        <script type="text/javascript" src="/static/amanda/js/plugins/jquery-ui-1.8.16.custom.min.js"></script>
        <link rel="stylesheet" href="/static/amanda/css/plugins/jquery.ui.css" type="text/css" />
        <style>
            .vernav2 {
                top: 78px;
            }
        </style>
    </head>

    <body class="withvernav">
        <div class="bodywrapper">
            <div class="topheader">
                <div class="left">
                    <h1 class="logo">棒棒糖~~<span>投票</span></h1>
                    <br clear="all" />
                </div><!--left-->

                <div class="right">
                    <div class="userinfo">
                        <img src="/static/amanda/images/thumbs/avatar.png" alt="" />
                        <span>Juan Dela Cruz</span>
                    </div><!--userinfo-->

                    <div class="userinfodrop">
                        <div class="avatar">
                            <a href=""><img src="/static/amanda/images/thumbs/avatarbig.png" alt="" /></a>
                            <div class="changetheme">
                                切换主题: <br />
                                <a class="default"></a>
                                <a class="blueline"></a>
                                <a class="greenline"></a>
                                <a class="contrast"></a>
                                <a class="custombg"></a>
                            </div>
                        </div><!--avatar-->
                        <div class="userdata">
                            <h4>Juan Dela Cruz</h4>
                            <span class="email">xuelong2013@gmail.com</span>
                            <ul>
                                <li><a href="editprofile.html">Edit Profile</a></li>
                                <li><a href="accountsettings.html">Account Settings</a></li>
                                <li><a href="help.html">Help</a></li>
                                <li><a href="index.html">Sign Out</a></li>
                            </ul>
                        </div><!--userdata-->
                    </div><!--userinfodrop-->
                </div><!--right-->
            </div><!--topheader-->
            <div class="header" style="min-height:10px;"> </div>

            <div class="vernav2 iconmenu">
                <ul>
                    <?php
                    $title = '';
                    $menus = App_Menu::getMenu();
                    foreach ($menus as $k => $menuConf):
                        if (!empty($menuConf['children'])):
                            ?>
                    <li class="current"><a href="#ele<?= $k ?>" class="elements"><?= $menuConf['text'] ?></a>
                                <span class="arrow"></span>
                                <ul id="ele<?= $k ?>">
                                    <?php foreach ($menuConf['children'] as $v): ?>
                                        <li><a href="<?= $v['link'] ?>"><?= $v['text']; ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="current">
                                <a href="<?= $menuConf['link'] ?>" class="widgets"><?= $menuConf['text'] ?></a>
                            </li>
                        <?php
                        endif;
                    endforeach;
                    ?>
                </ul>
                <a class="togglemenu"></a>
            </div><!--leftmenu-->