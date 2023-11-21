<div class="modal fade in" id="managerModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">MANAGER</h4>
      </div>
      <div class="modal-body" style="padding: 0px;">
        <iframe src="/admin/manager" style="width:100%;height: 700px;border:none;overflow:hidden;"></iframe>
      </div>

    </div>

  </div>
</div>

<div class="modal fade in" id="modalDiv" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" id="modalSize">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title" id="modalTitle"></h4>
      </div>
      <div id="modalContent">
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="//code.jquery.com/jquery-3.3.1.min.js"></script>


<script type="text/javascript" src="https://kit.fontawesome.com/1bf8354f0a.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
<script type="text/javascript" src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="//unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<link href="//gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="//gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script src="{{asset('admin-view/js/admin/bootstrap-select.js')}}"></script>
<script type="text/javascript" src="{{asset('admin-view/js/datepicker/js/bootstrap-datepicker.min.js')}}"></script>
<script type="text/javascript" src="{{asset('admin-view/js/datepicker/locales/bootstrap-datepicker.en.min.js')}}"></script>
<script src="{{asset('/admin-view/js/admin/toastDemo.js')}}"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{asset('admin-view/js/admin/script.js?t='.time())}}"></script>
<script src="{{asset('admin-view/js/admin/script-2.js?t='.time())}}"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="{{asset('admin-view/js/admin/jquery.tinytoggle.min.js')}}"></script>
<style>
  .error{
    color:red;
    margin-bottom: 10px;;
  }
</style>
<script type="text/javascript">
   $(document).ready(function() {
    $('.select2').select2();
});
  $(document).ready(function() {

    var site_url = $('head base').attr('href');

   

    $(".service-sortable").sortable({
      handle: '.handle',
      update: function(event, ui) {
        var array = [];
        $(this).find('tr').each(function(i) {
          $(this).attr('data-line', i + 1);
          var params = {};
          params['id'] = $(this).attr('data-id');
          params['line'] = $(this).attr('data-line');
          array.push(params);
        });
        $.post(site_url + 'admin/ajax_data', {
          action: 'service-sortable',
          services: array
        });
      }
    });

    $(".methods-sortable").sortable({
      handle: '.handle',
      update: function(event, ui) {
        var array = [];
        $(this).find('tr').each(function(i) {
          $(this).attr('data-line', i + 1);
          var params = {};
          params['id'] = $(this).attr('data-id');
          params['line'] = $(this).attr('data-line');
          array.push(params);
        });
        $.post(site_url + 'admin/ajax_data', {
          action: 'paymentmethod-sortable',
          methods: array
        });
      }
    });

    $(".category-sortable").sortable({
      handle: '.handle',
      update: function(event, ui) {
        var array = [];
        $(this).find('.categories').each(function(i) {
          $(this).attr('data-line', i + 1);
          var params = {};
          params['id'] = $(this).attr('data-id');
          params['line'] = $(this).attr('data-line');
          array.push(params);
        });
        $.post(site_url + 'admin/ajax_data', {
          action: 'category-sortable',
          categories: array
        });
      }
    });

  });
  $(function() {
    $('[data-toggle="tooltip"]').tooltip()
  });
</script>
@yield('scripts')


</body>

</html>