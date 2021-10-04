<?php 
if (!@$id) {
 show_404();exit;
}
include "template/header.php";
?>

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="wrapper">
            <div class="container-fluid">
                <!-- start page title -->
                <div class="page-title-alt-bg"></div>
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="<?php echo base_url("vc/$userType/dashboard"); ?>">Home</a></li>
                            <li class="breadcrumb-item active">Permission</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Permission Page</h4>
                </div> 
                <!-- end page title -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                        <section class="content" id="singlePage">
                          <h4>System Modules</h4>
                          <div class="alert alert-info"><b>NOTE:</b> Write permission means the user can both read and write to make changes on the page. It is therefore the highest permission a user can have on a page. <br>
                          <b>NOTE:</b> Also that your update only take effect when your changes is saved by clicking the save button below.
                          </div>
                          <div style="border-bottom: solid 2px #ccc;margin-bottom: 15px;"></div>
                          <div class="content-area">
                            <div>
                              <div class="row">
                                <div class="col-md-4">
                                  <div class="box-green box box-default box-solid collapsed-box" style="height: auto;">
                                    <div class="box-header with-border">
                                      <h3 class="box-title">Admin Dashboard</h3>
                                      <div class="box-tools pull-right">
                                        <button type="button" style="margin-top:1px;" class="btn btn-box-tool" data-widget="collapse"><i class="icon-plus3" style="color:#606C84;font-size:18px;"></i>
                                        </button>
                                      </div>
                                      <!-- /.box-tools -->
                                    </div>
                                  </div>
                                  <div class="box-body">
                                    <ul style="list-style: none;">
                                      <li>
                                          <div class="permission-content form-group row">
                                            <input type="hidden" value="<?php echo 'vc/admin/dashboard'; ?>" class="pageLink">
                                            <lable class="col-sm-5 col-form-label">Dashboard</lable>
                                            <div class="col-sm-5">
                                              <select class="form-control">
                                                <option value="">..deny..</option>
                                                <option value="r">Read</option>
                                                <option value="w" selected="selected">Write</option>
                                              </select>
                                            </div>
                                          </div>
                                        </li>
                                    </ul>
                                  </div>
                                </div>

                              <?php 
                                $param = array(
                                  array('id'=>'r','value'=>'Read'),
                                   array('id'=>'w','value'=>'Write')
                                );
                               ?>
                                <?php foreach ($allPages as $module => $pages): ?>
                                <?php 
                                    $state='';
                                    if ($permitPages[$module]['state']===1) {
                                      $state='box-default box-solid';
                                    }
                                     if ($permitPages[$module]['state']===2) {
                                      $state='box-success box-solid';
                                    }
                                     if ($permitPages[$module]['state']===0) {
                                      $state='box-default';
                                    }
                                 ?>
                                <div class="col-md-4">
                                  <div class="box-green box <?php echo $state; ?> collapsed-box" style="height: auto;">
                                    <div class="box-header with-border">
                                      <h3 class="box-title"><?php echo $module; ?> </h3>
                                      <div class="box-tools pull-right">
                                        <button type="button" style="margin-top:1px;" class="btn btn-box-tool" data-widget="collapse"><i class="icon-plus3" style="color:#606C84;font-size:18px;"></i>
                                        </button>
                                      </div>
                                      <!-- /.box-tools -->
                                    </div>
                                    <!-- /.box-header -->
                                    <div class="box-body">
                                      <ul style="list-style: none;">
                                      <?php foreach ($pages['children'] as $key => $page): ?>
                                        <li>
                                          <div class="permission-content form-group row">
                                            <input type="hidden" value="<?php echo $page; ?>" class="pageLink">
                                            <lable class="col-sm-5 col-form-label"><?php echo $key; ?></lable>
                                            <div class="col-sm-5">
                                              <select class="form-control">
                                                <option value="">..deny..</option>
                                                <?php echo buildOption($param,isset($allStates[$page])?$allStates[$page]:''); ?>
                                              </select>
                                            </div>
                                          </div>
                                        </li>
                                      <?php endforeach; ?>
                                      </ul>
                                    </div>
                                    <!-- /.box-body -->
                                  </div>
                                  <!-- /.box -->
                                </div>
                                <?php endforeach; ?>

                                  <div class="clearfix"></div>
                              </div>
                            <?php if (true): ?>
                              <form id="perm_form" action="<?php echo base_url('ajaxData/savePermission'); ?>" method="post">
                                <input type="hidden" value="<?php echo $id; ?>" name="role">
                                <input type="hidden" name="remove" id="perm_remove">
                                <input type="hidden" name="update" id="perm_update">
                                <button type='submit' name='sub' class="btn btn-primary btn-block waves-effect width-sm save float-right">Save</button>
                              </form>
                            <?php endif; ?>
                            </div>
                        </section>
                    </div>
                  </div>
                </div>
                
            </div> <!-- end container -->
        </div>
        <!-- end wrapper -->
      </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

<?php include "template/footer.php"; ?>
<script>
    $(document).ready(function($){
      $('#perm_form').submit(function(event) {
        event.preventDefault();
        var update=[];
        var remove=[];
        $('.permission-content').each(function(index, el) {
          var link =$(this).children('.pageLink').val();
          var perm = $(this).find('select').val();
          if (!perm) {
            remove.push(link);
          }
          else{
            update.push({path:link,permission:perm});
          }
        });
        //stringify the content and send it to the server straight
        var removeString = JSON.stringify(remove);
        var updateString = JSON.stringify(update);
        $('#perm_remove').val(removeString);
        $('#perm_update').val(updateString);
        submitAjaxForm($(this));
      });
    });

    function ajaxFormSuccess(target,data) {
      if (data.status) {
        reportAndRefresh(target,data);
        return;
      }
      showNotification(data.status,data.message);
        
    }
  </script>
