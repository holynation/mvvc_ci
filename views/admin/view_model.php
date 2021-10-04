<?php include_once 'template/header.php'; ?>
<!-- this is the sidebar position -->

<!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

  <div class="content-wrapper">
    <!-- Page header -->
    <div class="page-header page-header-light">
      <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
          <h4><i class="icon-arrow-left52 mr-2"></i> <span class="font-weight-semibold"><?php echo ucfirst($userType); ?> </span> - <?php echo ucfirst(removeUnderscore(@$model)); ?> Page</h4>
          <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
      </div>

      <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
        <div class="d-flex">
            <div class="breadcrumb">
              <a href="<?php echo base_url("vc/$userType/dashboard"); ?>" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
              <?php
                $modelName = (@$_GET['type'] == 'activate' && $modelName == 'activated') ? removeUnderscore($modelName) : removeUnderscore($modelName);
              ?>
              <a href="#" class="breadcrumb-item"><?php echo ucfirst(@$modelName); ?></a>
              <span class="breadcrumb-item active">Current</span>
            </div>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
      </div>
    </div>
    <!-- /page header -->

    <!-- Content area -->
    <div class="content">
      <!-- Basic card -->
      <div class="card">
        <div class="card-header header-elements-inline">
          <h5 class="card-title"></h5>
          <div class="header-elements">
            <div class="list-icons">
              <a class="list-icons-item" data-action="collapse"></a>
            </div>
          </div>
        </div>
        <!-- this is the view table for each model -->
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
                  <?php
                  $enableParam=array();
                  $action = array('delete'=>"delete/$modelName",'edit'=>"edit/$modelName");
                  if($modelName == 'post_media'){
                    $action['enable'] = 'getEnabled';
                  }else if($modelName == 'Activated' || $modelName == 'Unactivated' || $modelName == 'payment' || $modelName == 'cashback'){
                    $action = array();
                  }

                  $param = isset($dataParam) ? array($dataParam) : array();
                  // $tableData = $this->queryHtmlTableModel->getHtmlTableWithQuery($viewHistory,$param,$count,$action);
                    $tableData = $this->queryHtmlTableObjModel->openTableHeader($viewHistory,$param,null,array('id'=>'datatable-buttons-customer'))
                    // ->paging(true,0,10)
                    ->excludeSerialNumber(true)
                    ->appendTableAction($action)
                    ->generateTable();
                  echo $tableData;
                  ?>
            </div>
          </div>
        </div>
      </div>
      <!-- /basic card -->
    </div>
    <!-- /content area -->

    <div class="modal fade" id="modal-edit">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"></h4>
          </div>
          <div class="modal-body">
            <p id="edit-container">
              
            </p>
          </div>
        </div>
        <!-- /.modal-content -->
      </div>
      <!-- /.modal-dialog -->
    </div>

<!-- ============================================================== -->
<!-- End Page content -->
<!-- ============================================================== -->

<?php include_once 'template/footer.php'; ?>

<script>
 function addMoreEvent() {
   $('li[data-ajax-edit=1] a').click(function(event){
     event.preventDefault();
     var link = $(this).attr('href');
     var action = $(this).text();
     sendAjax(null,link,'','get',showUpdateForm);
   });
 }
  function showUpdateForm(target,data) {
     var data = JSON.parse(data);
     if (data.status==false) {
       showNotification(false,data.message);
       return;
     }
    var container = $('#edit-container');
      container.html(data.message);
      //rebind the autoload functions inside
      $('#modal-edit').modal();
   }
  function ajaxFormSuccess(target,data) {
    showNotification(data.status,data.message);
    if (data.status) {
      location.reload();
    }
  }
</script>

