<?php 
use Ssg\Core\Config;
?>

<h1>Change Password for user <strong><?php echo $this->user_name; ?></strong></h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>
<br />
<br />
<p>
<form  class="form-horizontal" method="post" action="<?php echo Config::get('URL'); ?>login/setNewPassword" name="new_password_form">
    
    <input type='hidden' name='user_name' value='<?php echo $this->user_name; ?>' />
    
     <div class="form-group">
        <label for="reset_input_password_new" class="col-sm-2 control-label">Enter New Password</label>
        <div class="col-sm-4">
    	  <input id="reset_input_password_new"  class="form-control input-sm"  type="password" name="user_password_new" pattern=".{6,}" placeholder="New Password" required autocomplete="off" />
        </div>
        <div class="col-sm-6">
          <p>Minimun 6 characters</p>
        </div>
      </div>
      
       <div class="form-group">
        <label for="reset_input_password_repeat" class="col-sm-2 control-label">Repeat new password</label>
        <div class="col-sm-4">
          <input id="reset_input_password_repeat" class="form-control input-sm"  type="password" name="user_password_repeat" pattern=".{6,}" placeholder="Repeat New Password" required autocomplete="off" />
        </div>
        <div class="col-sm-6">
          <p>Must be the same as the new password</p>
        </div>
      </div>
      
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" name="submit_new_password" class="btn btn-default">Submit new password</button>
        </div>
      </div>
</form>
</p>