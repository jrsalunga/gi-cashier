<nav id="nav-action" class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-form">
      <div class="btn-group" role="group">
        <a href="/dashboard" class="btn btn-default" title="Back to Main Menu">
          <span class="gly gly-unshare"></span> 
          <span class="hidden-xs hidden-sm">Back</span>
        </a> 
        <div class="btn-group">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="fa fa-archive"></span> 
            <span class="hidden-xs hidden-sm">Filing System</span>
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="/backups"><span class="fa fa-file-archive-o"></span> Backup</a></li>
            <li><a href="/{{brcode()}}/depslp"><span class="fa fa-bank"></span> Deposit Slip</a></li>
            <li><a href="/{{brcode()}}/ap"><span class="fa fa-briefcase"></span> Payables</a></li>
          </ul>
        </div>
        
        <div class="btn-group">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="fa fa-calendar-check-o"></span>
            <span class="hidden-xs hidden-sm">Checklist</span>
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="/backups/checklist"><span class="fa fa-file-archive-o"></span> Backup</a></li>
            <li><a href="/{{brcode()}}/depslp/checklist"><span class="fa fa-bank"></span> Deposit Slip</a></li>
          </ul>
        </div>

        <div class="btn-group">
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="glyphicon glyphicon-th-list"></span>
            <span class="hidden-xs hidden-sm">Logs</span>
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="/backups/log"><span class="fa fa-file-archive-o"></span> Backup</a></li>
            <li><a href="/{{brcode()}}/depslp/log"><span class="fa fa-bank"></span> Deposit Slip</a></li>
          </ul>
        </div>
      </div> <!-- end btn-grp -->
      <div class="btn-group" role="group">
        <a href="/{{brcode()}}/uploader" class="btn btn-default">
          <span class="glyphicon glyphicon-cloud-upload"></span>
          <span class="hidden-xs hidden-sm">DropBox</span>
        </a>
      </div>
      <!--
      <div class="btn-group" role="group">
        <a href="/backups/upload" class="btn btn-default">
          <span class="glyphicon glyphicon-cloud-upload"></span> DropBox
        </a>
      </div>
    -->
    </div>
  </div>
</nav>