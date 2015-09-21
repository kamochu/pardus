<?php
use \Ssg\Core\Config;
?>
<span class="pull-right">
	<a class="btn btn-primary" href="<?php echo Config::get('URL'); ?>service/all/" role="button">All Services</a>
</span>
<h1>Edit Service</h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>
<h3>Edit Service Form</h3>


<form class="form-horizontal" method="post">
  <div class="form-group">
    <label for="service_id" class="col-sm-2 control-label">Service ID</label>
    <div class="col-sm-4">
      <input type="text" disabled="disabled" class="form-control input-sm" name="service_id" id="service_id" placeholder="Service ID" value="<?= $this->service_id ?>">
    </div>
    <div class="col-sm-6">
      <p>SDP Sevice ID e.g. <strong>6013202000002998</strong> </p>
    </div>
  </div>
  <div class="form-group">
    <label for="service_name" class="col-sm-2 control-label">Service Name</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" name="service_name" id="service_name" placeholder="Service Name" value="<?= $this->service_name ?>">
    </div>
    <div class="col-sm-6">
      <p>Service Instance Name e.g. <strong>Bulk_22652_Global</strong> </p>
    </div>
  </div>
  <div class="form-group">
    <label for="service_type" class="col-sm-2 control-label">Service Type</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" name="service_type" id="service_type" placeholder="Service Type" value="<?= $this->service_type ?>">
    </div>
    <div class="col-sm-6">
      <p><strong>1</strong> - On Demand Service, <strong>2</strong> - Bulk Service, <strong>3</strong> - Subscription Service </p>
    </div>
  </div>
  <div class="form-group">
    <label for="short_code" class="col-sm-2 control-label">Short Code</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" name="short_code" id="short_code" placeholder="Short Code" value="<?= $this->short_code ?>">
    </div>
    <div class="col-sm-6">
      <p>Service Access Code e.g. <strong>22652</strong> </p>
    </div>
  </div>
  <div class="form-group">
    <label for="criteria" class="col-sm-2 control-label">Criteria</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" name="criteria" id="criteria" placeholder="Criteria" value="<?= $this->criteria ?>">
    </div>
    <div class="col-sm-6">
      <p>Service Instance Criteria e.g. <strong>Love</strong> (all messages meeting criteria Love will be sent by SDP to service endpoint below)</p>
    </div>
  </div>
  <div class="form-group">
    <label for="service_endpoint" class="col-sm-2 control-label">Service URL</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" name="service_endpoint" id="service_endpoint" placeholder="Service URL" value="<?= $this->service_endpoint ?>">
    </div>
    <div class="col-sm-6">
      <p>Service URL used by SDP to forward all messages that meet above criteria e.g. <strong>http://197.237.13.55:49200/ssg/notify/sms/</strong> For on demand and <strong>http://197.237.13.55:49200/ssg/subscription/request/</strong> for subscriptions</strong></p>
    </div>
  </div>
  <div class="form-group">
    <label for="delivery_notification_endpoint" class="col-sm-2 control-label">Delivery Notification URL</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" name="delivery_notification_endpoint" id="delivery_notification_endpoint" placeholder="Delivery Notification URL" value="<?= $this->delivery_notification_endpoint ?>">
    </div>
    <div class="col-sm-6">
      <p>Delivery Receipt Notification URL used by SDP to delivery receipts e.g. <strong>http://197.237.13.55:49200/ssg/delivery/receipt/</strong> </p>
    </div>
  </div>
  <div class="form-group">
    <label for="interface_name" class="col-sm-2 control-label">Interface Name</label>
    <div class="col-sm-4">
      <input type="text" class="form-control input-sm" id="interface_name" name="interface_name" placeholder="Interface Name" value="<?= $this->interface_name ?>">
      <input type="hidden" id="action" name="action" value="add">
      <input type="hidden" id="id" name="id" value="<?= $this->id ?>">
    </div>
    <div class="col-sm-6">
      <p>This applies for on demand service only. Use <strong>notifySmsReception</strong> </p>
    </div>
  </div>
  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
      <button type="submit" name="submit" class="btn btn-default">Submit</button>
    </div>
  </div>
</form>