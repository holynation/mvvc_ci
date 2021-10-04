<?php include_once 'template/header.php'; ?>
<!-- this is the sidebar position -->
<?php
$exclude = ($configData && array_key_exists('exclude', $configData))?$configData['exclude']:array();
$has_upload = ($configData && array_key_exists('has_upload', $configData))?$configData['has_upload']:false;
$hidden = ($configData && array_key_exists('hidden', $configData))?$configData['hidden']:array();
$showStatus = ($configData && array_key_exists('show_status', $configData))?$configData['show_status']:false;
$showAddForm = ($configData && array_key_exists('show_add', $configData))?$configData['show_add']:true;
$submitLabel = ($configData && array_key_exists('submit_label', $configData))?$configData['submit_label']:"Save";
$extraLink = ($configData && array_key_exists('extra_link', $configData))?$configData['extra_link']:false;
$extraValue = ($configData && array_key_exists('extra_value', $configData))?$configData['extra_value']:"Add";
$tableAction = ($configData && array_key_exists('table_action', $configData))?$configData['table_action']:$model::$tableAction;
$tableExclude = ($configData && array_key_exists('table_exclude', $configData))?$configData['table_exclude']:array();
$query = ($configData && array_key_exists('query', $configData))?$configData['query']:"";
$tableTitle = ($configData && array_key_exists('table_title', $configData))?$configData['table_title']:"Table of ".ucfirst(removeUnderscore($model));
$icon = ($configData && array_key_exists('table_icon', $configData))?$configData['table_icon']:"";
$search = ($configData && array_key_exists('search', $configData))?$configData['search']:"";
$searchPlaceholder = ($configData && array_key_exists('search_placeholder', $configData))?$configData['search_placeholder']:"";
$searchOrderBy = ($configData && array_key_exists('order_by', $configData))?$configData['order_by']:"";
$filter = ($configData && array_key_exists('filter', $configData))?$configData['filter']:"";
$show_add = ($configData && array_key_exists('show_add', $configData))?$configData['show_add']:true;
$checkBox = ($configData && array_key_exists('table_checkbox', $configData))?$configData['table_checkbox']:false;
$tableAttr = ($configData && array_key_exists('table_attr', $configData))?$configData['table_attr']:array();
$editMessageInfo = ($configData && array_key_exists('edit_message_info', $configData))?$configData['edit_message_info']:"";
$headerTitle = ($configData && array_key_exists('header_title', $configData))?$configData['header_title']:"";

$where ='';
$orderBy=' order by ID desc';
if ($filter) {
  foreach ($filter as $item) {
    $display = (isset($item['filter_display'])&&$item['filter_display'])?$item['filter_display']:$item['filter_label'];

    if (isset($_GET[$display]) && $_GET[$display]) {
      $value = $this->db->conn_id->escape_string($_GET[$display]);
      $where.= $where?" and {$item['filter_label']}='$value' ":"where {$item['filter_label']}='$value' ";
    }
  }
}

if ($search) {
 $val = isset($_GET['q'])?$_GET['q']:'';
 $val = $this->db->conn_id->escape_string($val);
  if (isset($_GET['q']) && $_GET['q']) {
    $whereQ = (strpos($query,'where') !== false) ? " and " : "where ";
    $temp=$where?" and (":" $whereQ ";
    $count =0;
    foreach ($search as $criteria) {
      $temp.=$count==0?" $criteria like '%$val%'":" or $criteria like '%$val%' ";
      $count++;
    }
    // $temp.=')';
    $temp .= (strpos($temp, 'and (') !== false) ? ")" : "";
    $where.=$temp;
  }
}

if (isset($_GET['export'])) {
  $this->queryHtmlTableModel->export=true;
  $this->tableViewModel->export=true;
}

$tableData='';

if($query) {
  $query.=' '.$where;
  if($searchOrderBy){
    $countFil = 0;
    $tempOrder='';
    foreach($searchOrderBy as $valFilter){
      $tempOrder .= $countFil == 0? " $valFilter " : " , $valFilter ";
      $countFil++;
    }
    $query .= "order by $tempOrder desc";
  }
  // $tableData= $this->queryHtmlTableModel->getHtmlTableWithQuery($query,array(),$count,$tableAction,$header=null,$paged=true,$lower=0, $length=NULL,$parentModel=null,$excludeArray=array(),$appendForm=array(),$tableAttr);
// echo $query;exit;
  $tableData = $this->queryHtmlTableObjModel->openTableHeader($query,array(),null,$tableAttr,$tableExclude)
                ->excludeSerialNumber(true)
                ->paging(true,0,50)
                ->appendTableAction($tableAction,null)
                ->appendCheckBox($checkBox,array('class'=>'form-control'))
                ->generateTable();
}
else{
 // $tableData= $this->tableWithHeaderModel->getTableHtml($model,$count,$tableExclude,$tableAction,true,0,null,true,$orderBy,$where,array(),false,$tableAttr);
  $tableData = $this->tableWithHeaderModel->openTableHeader($model,$tableAttr,$tableExclude,true)
  ->excludeSerialNumber(true)
  ->appendTableAction($tableAction)
  ->appendEmptyIcon('<i class="icon-stack-empty mr-2 mb-2 icon-2x"></i>')
  ->generateTableBody()
  ->pagedTable(true,20)
  ->generate();
}
?>

<div>
    <?php
    $modelPath = null;
    $extra = "";

    $formContent= $this->modelFormBuilder->start($model.'_table')
    ->appendInsertForm($model,true,$hidden,'',$showStatus,$exclude)
    ->addSubmitLink($modelPath)
    ->appendExtra($extra)
    ->appendResetButton('Reset','btn btn-outline bg-danger-400 text-danger-400 border-danger-400 rounded-round btn-lg')
    ->appendSubmitButton($submitLabel,'btn btn-outline bg-success-400 text-success-400 border-success-400 rounded-round btn-lg')
    ->build();
    ?>
</div>

<!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

  <div class="content-wrapper">
    <!-- Page header -->
    <div class="page-header page-header-light">
      <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
          <?php
            $pageTitle = ucfirst(removeUnderscore(@$model))." Page";
            if($headerTitle){
              $pageTitle = $headerTitle;
            }
          ?>
          <h4><i class="icon-arrow-left52 mr-2"></i> <span class="font-weight-semibold">
            <?php echo ucfirst($userType); ?> </span> - <?php echo $pageTitle; ?> </h4>
          <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>

        <div class="header-elements d-none">
          <div class="d-flex justify-content-center">
            <?php if($show_add && $userType != 'customer'): ?>
              <a href="javascript:void(0);" data-toggle='modal' data-target='#myModal'>
                  <button class="btn btn-labeled btn-labeled-right bg-primary waves-effect width-md float-right">
                      <i class="icon icon-add"></i>  Add new <?php echo removeUnderscore(@$model); ?>(s)
                  </button>
              </a>
            <?php if($has_upload): ?>
            <a href="javascript:void(0);" class="btn btn-labeled btn-labeled-right width-md ml-1" data-toggle='modal' data-target='#modal-upload'>Batch Upload <b><i class="icon-make-group "></i></b>
            </a>
            <?php endif; ?> <!-- end batch upload -->
             <?php endif; ?> <!-- end the show add -->
          </div>
        </div> <!-- end the header-elements-->
      </div>

      <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
        <div class="d-flex">
            <div class="breadcrumb">
              <a href="<?php echo base_url("vc/$userType/dashboard"); ?>" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
              <a href="#" class="breadcrumb-item"><?php echo ucfirst(removeUnderscore(@$model)); ?></a>
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
            <h5 class="card-title"><?php echo $tableTitle; ?></h5>
            <div class="header-elements">
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                </div>
            </div>
        </div>
        <div>
          <?php $where=''; ?>
          <form action="">
            <div class="row col-lg-12">
              <?php 
              if ($filter): ?>
                <?php foreach ($filter as $item): ?>
                 <?php $display = (isset($item['filter_display'])&&$item['filter_display'])?$item['filter_display']:$item['filter_label']; ?>
                  <?php 
                    if (isset($_GET[$display]) && $_GET[$display]) {
                      $value = $this->db->escape_str($_GET[$display]);
                      $where.= $where?" and {$item['filter_label']}='$value' ":"where {$item['filter_label']}='$value' ";
                    }
                  ?>
                <div class="form-group">
                  <div class="col-lg-12">
                    <select class="form-control <?php echo isset($item['child'])?'autoload':'' ?>" name="<?php echo $display; ?>" id="<?php echo $display; ?>" <?php echo isset($item['child'])?"data-child='{$item['child']}'":""?> <?php echo isset($item['load'])?"data-load='{$item['load']}'":""?> >
                      <option value="">..select <?php echo removeUnderscore(rtrim($display,'_id')) ?>...</option>
                        <?php if (isset($item['preload_query'])&& $item['preload_query']): ?>
                          <?php echo buildOptionFromQuery($this->db,$item['preload_query'],null,isset($_GET[$display])?$_GET[$display]:''); ?>
                        <?php endif; ?>
                          <!-- end for the option value -->
                    </select>
                  </div>
                </div>
              <?php endforeach; ?> <!-- end foreach for filter looop -- >
            <?php endif; ?> <!-- end if filter -->

            <?php if ($search): ?>

            <?php 
              $filterLabel = ($searchPlaceholder) ? $searchPlaceholder : $search;
              $placeholder = implode(',', $filterLabel);
              $val = isset($_GET['q'])?$_GET['q']:'';
              $val = $this->db->escape_str($val);
             ?>
            <div class="row form-group ml-1">
              <div class="col-lg-12"><input class="form-control" type="text" name="q" placeholder="<?php echo $placeholder; ?>" value="<?php echo $val; ?>">
              </div>
            </div>
            <?php endif; ?> <!-- end the search input -->

            <?php if ($search || $filter): ?>
              <div class="form-group col-lg-3">
                <input type="submit" value="Filter" class="btn btn-dark btn-block">
              </div>
            </div>
            <?php endif; ?> <!-- end submit filter -->
          </form>
         <br>

        <!-- this is the view table for each model -->
        <div class="card-body table-responsive">
            <?php echo $tableData; ?>
        </div>
      </div>
      <!-- /basic card -->
    </div>
    <!-- /content area -->

    <?php if ($configData==false || array_key_exists('has_upload', $configData)==false || $configData['has_upload']): ?>
      <div class="modal modal-default fade" id="modal-upload">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><?php echo removeUnderscore($model) ?> Batch Upload</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <?php
                $batchUrl = "mc/template/$model?exec=name";
                $batchActionUrl = "mc/sFile/$model";
              ?>
              <div>
                <a  class='btn btn-info' href="<?=base_url($batchUrl)?>">Download Template</a>
              </div>
              <br/>
              <h3>Upload <?php echo removeUnderscore($model) ?></h3>
              <form method="post" action="<?php echo base_url($batchActionUrl) ?>" enctype="multipart/form-data">
                <div class="form-group">
                  <input type="file" name="bulk-upload" class="form-control">
                  <input type="hidden" name="MAX_FILE_SIZE" value="4194304">
                </div>
                <div class="form-group">
                  <input type="submit" class='btn btn-success' name="submit" value="Upload">
                </div>
              </form>
            </div>
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- /.modal -->
    <?php endif; ?>

      <!-- this is add modal -->
      <div>
        <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"><?php echo removeUnderscore($model);  ?> Entry Form</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
              </div>
              <div class="modal-body">
                <p><?php echo $formContent; ?></p>
              </div>
            </div><!-- /.modal-content -->
          </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
      </div>
      
      <!-- this is for the edit -->
      <div class="row">
        <div id="modal-edit" class="modal fade animated" role="dialog">
          <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title"></h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                  <?php if(@$editMessageInfo != ""): ?>
                  <div class="alert alert-danger" style="background-color: #ea2825;color:#fff;">
                    <b><?php echo @$editMessageInfo; ?></b>
                  </div>
                <?php endif; ?>
                  <p id="edit-container">
                  </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" id="close" data-dismiss="modal">Close
                    </button>
                </div>
            </div>
          </div>
        </div>
      </div>

<!-- ============================================================== -->
<!-- End Page content -->
<!-- ============================================================== -->
</div>
<?php include_once 'template/footer.php'; ?>
</div>
<script>
    var inserted=false;
      $(document).ready(function($) {
        $('.modal').on('hidden.bs.modal', function (e) {
          if (inserted) {
            inserted = false;
            location.reload();
          }
      });
      $('.close').click(function(event) {
        if (inserted) {
          inserted = false;
          location.reload();
        }
      });
      $('li[data-ajax-edit=1] a').click(function(event){
        event.preventDefault();
        var link = $(this).attr('href');
        var action = $(this).text();
        sendAjax(null,link,'','get',showUpdateForm);
      });
      // using this for file upload input
      loadFIleInput();
    });
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
      if (data.status) {
        inserted=true;
        $('form').trigger('reset');
      }
      showNotification(data.status,data.message);
      if (typeof target ==='undefined') {
        location.reload();
      }
    }
  </script>
