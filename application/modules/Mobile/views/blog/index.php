<?php require dirname(dirname(__FILE__)) . '/include/index_header.php'; ?>

<div class="centercontent tables">
    <script type="text/javascript">
        var MyForm = function() {
            var searchFields = ['title', 'content', 'name'];
            var i;
            var field;
            this.search = function() {
                for (i in searchFields) {
                    field = searchFields[i];
                    $('#' + field).val($('#sch_' + field).val());
                }
                $('#my_form').submit();
            };
        };

        var MyIframe = function() {
            this.edit = function() {
                $('#my_iframe').attr({src: '', width: "800", height: "900"});
                var dialogOptions = {
                    minWidth: 820,
                    minHeight: 400,
                    modal: true,
                    title: '删除',
                    closeText: "关闭",
                    closeOnEscape: true,
                    height: 900,
                    width: 840,
                };
                $("#div_iframe").dialog(dialogOptions);
            };
        };

        var myForm = new MyForm();
        var myIframe = new MyIframe();
        
        $(function() {
            $('#edit').click(function(){
                myIframe.edit();
            });
        });
    </script>

    <div id="contentwrapper" class="contentwrapper">
        <div class="contenttitle2">
            <h3>欢迎页</h3>
        </div>

        <form id="my_form" action="<?= $url['url'] ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="title" id="title" value="" />
            <input type="hidden" name="content" id="content" value="" />
            <input type="hidden" name="name" id="name" value="" />
        </form>

        <div class="tableoptions">
            标题：<input type="text" name="title" id="sch_title" value="<?= $search['title'] ?>" />
            内容：<input type="text" name="content" id="sch_content" value="<?= $search['content'] ?>" />
            年份：
            <select name="name" id="name">
                <option value="" selected="selected">Show All</option>
                <option value="">Rendering Engine</option>
                <option value="">Platform</option>
            </select>
            <a class="btn btn_search" href="javascript:void(0)" onclick="myForm.search();">
                <span>搜索</span>
            </a>
            <a class="btn btn_grid ele_add_btn" href="javascript:void(0)">
                <span>添加</span>
            </a>
        </div>

        <table cellpadding="0" cellspacing="0" border="0" id="table2" class="stdtable stdtablecb">
            <colgroup>
                <col class="con0" style="width: 4%" />
                <col class="con1" />
                <col class="con0" />
                <col class="con1" />
                <col class="con0" />
                <col class="con1" />
                <col class="con0" />
                <col class="con1" />
            </colgroup>
            <thead>
                <tr>
                    <th class="head0"><input type="checkbox" class="checkall" /></th>
                    <th class="head1">Rendering engine</th>
                    <th class="head0">Browser</th>
                    <th class="head1">Platform(s)</th>
                    <th class="head0">Engine version</th>
                    <th class="head1">CSS grade</th>
                    <th class="head1">operate</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td align="center"><input type="checkbox" /></td>
                    <td>Trident</td>
                    <td>Internet Explorer 4.0</td>
                    <td>Win 95+</td>
                    <td class="center">4</td>
                    <td class="center">X</td>
                    <td class="center"><a href="javascript:void(0)" class="edit" id="edit">Edit</a> &nbsp; <a href="" class="delete">Delete</a></td>
                </tr>
                <tr>
                    <td align="center"><input type="checkbox" /></td>
                    <td>Trident</td>
                    <td>Internet Explorer 5.0</td>
                    <td>Win 95+</td>
                    <td class="center">5</td>
                    <td class="center">C</td>
                    <td class="center"><a href="javascript:void(0)" class="edit">Edit</a> &nbsp; <a href="" class="delete">Delete</a></td>
                </tr>
                <tr>
                    <td align="center"><input type="checkbox" /></td>
                    <td>Trident</td>
                    <td>Internet  Explorer 5.5</td>
                    <td>Win 95+</td>
                    <td class="center">5.5</td>
                    <td class="center">A</td>
                    <td class="center"><a href="javascript:void(0)" class="edit">Edit</a> &nbsp; <a href="" class="delete">Delete</a></td>
                </tr>
                <tr>
                    <td align="center"><input type="checkbox" /></td>
                    <td>Trident</td>
                    <td>Internet Explorer 6</td>
                    <td>Win 98+</td>
                    <td class="center">6</td>
                    <td class="center">A</td>
                    <td class="center">
                        <a href="javascript:void(0)" class="edit">Edit</a> &nbsp; <a href="" class="delete">Delete</a>
                    </td>
                </tr>
                <tr>
                    <td align="center"><input type="checkbox" /></td>
                    <td>Trident</td>
                    <td>Internet Explorer 7</td>
                    <td>Win XP SP2+</td>
                    <td class="center">7</td>
                    <td class="center">A</td>
                    <td class="center"><a href="javascript:void(0)" class="edit">Edit</a> &nbsp; <a href="" class="delete">Delete</a></td>
                </tr>
            </tbody>
        </table>

        <div id="div_iframe" title="Basic dialog" style="display: none">
            <iframe frameborder="0" id="my_iframe">
            </ifram>
        </div>
    </div><!--contentwrapper-->
</div><!--centercontent-->


<?php require dirname(dirname(__FILE__)) . '/include/index_footer.php'; ?>
