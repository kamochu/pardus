<?php
use \Ssg\Core\Config;
?>
<h1>Delete Service</h1>
<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>

<span class="pull-right">
	<a class="btn btn-primary" href="<?php echo Config::get('URL'); ?>service/all/" role="button">All Services</a> &nbsp;
    <a class="btn btn-info" href="<?php echo Config::get('URL'); ?>service/edit/<?=$this->service_id?>" role="button">Edit Service</a> &nbsp;
    <a class="btn btn-danger" href="<?php echo Config::get('URL'); ?>service/delete/<?=$this->service_id?>" role="button">Delete Service</a>
</span>


<?php 
//display the content if the status variable is set
if (isset($this->status)) {
?>

<h3>Delete Service Form</h3>


<br>
<form class="form-inline" method="post">
	  <label for="service_id">Service ID</label>
      <input type="text" disabled class="form-control" name="service_id" id="service_id" placeholder="Service ID" value="<?= $this->service_id ?>">
      <input type="hidden" id="action" name="action" value="delete">
      <input type="hidden" id="id" name="id" value="<?= $this->id ?>">
      <button type="submit" name="submit" class="btn btn-danger">Confirm Deletion</button>
</form>


<h3>Service Information</h3>
<table class="table table-striped table-hover">
	<thead>
        <th width="30%">Service Parameter</th>
        <th>Value</th>
    </thead>
	<tr>
		<td>Status</td>
		<td>
        <?php 
			if ($this->status == 1) {
				//on
				?>
                <span class="btn btn-success  btn-xs">ON</span>
                <?php
			} else {
				//off
				?>
                 <span class="btn btn-warning  btn-xs">OFF</span>
                <?php
			}
		?>
        </td>
	</tr>
    <tr>
		<td>ID</td>
		<td><?= $this->id ?></td>
	</tr>
	<tr>
		<td>Service ID</td>
		<td><?= $this->service_id ?></td>
	</tr>
    <tr>
		<td>Service Name</td>
		<td><?= $this->service_name ?></td>
	</tr>
    <tr>
		<td>Service Type</td>
		<td><?= $this->service_type ?></td>
	</tr>
    <tr>
		<td>Short Code</td>
		<td><?= $this->short_code ?></td>
	</tr>
    <tr>
		<td>Service URL</td>
		<td><?= $this->service_endpoint ?></td>
	</tr>
    <tr>
		<td>Criteria</td>
		<td><?= $this->criteria ?></td>
	</tr>
    <tr>
		<td>Delivery Notification URL</td>
		<td><?= $this->delivery_notification_endpoint ?></td>
	</tr>
    <tr>
		<td>Interface Name</td>
		<td><?= $this->interface_name ?></td>
	</tr>
	<tr>
		<td>Correlator</td>
		<td><?= $this->correlator ?></td>
	</tr>
    <tr>
		<td>Creation Date</td>
		<td><?= $this->created_on ?></td>
	</tr>
    <tr>
		<td>Last Updated On</td>
		<td><?= $this->last_updated_on ?></td>
	</tr>
    <tr>
		<td>Last Updated By</td>
		<td><?= $this->last_updated_by ?></td>
	</tr>
</table>
<?php
}
?>
