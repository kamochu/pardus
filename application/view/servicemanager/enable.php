<?php 
use Ssg\Core\Config;
?>
<h1>Enable Service</h1>
<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>
<span class="pull-right">
	<a class="btn btn-primary" href="<?php echo Config::get('URL'); ?>service/all/" role="button">All Services</a> &nbsp;
	<a class="btn btn-warning" href="<?php echo Config::get('URL'); ?>service/disable/<?=$this->service_id?>" role="button">Disable Service</a> &nbsp;	
    <a class="btn btn-info" href="<?php echo Config::get('URL'); ?>service/edit/<?=$this->service_id?>" role="button">Edit Service</a>
</span>
<h3>Processing Outcome</h3>
<?php 
if ($this->result['result'] == 0) { //success
	?>
    <div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>The service <strong><?= $this->service_id ?></strong> has been enabled successfully. </div>
    <?php
} else { //failed 
	?>
    <div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>The service <strong><?= $this->service_id ?></strong> failed to enable. Error: <?= $this->result['result'] ?> - <?= $this->result['resultDesc'] ?> </div>
    <?php
}

?>
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
			if ($this->result['service']->status == 1) {
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
		<td><?= $this->result['service']->id ?></td>
	</tr>
	<tr>
		<td>Service ID</td>
		<td><?= $this->result['service']->service_id ?></td>
	</tr>
    <tr>
		<td>Service Name</td>
		<td><?= $this->result['service']->service_name ?></td>
	</tr>
    <tr>
		<td>Service Type</td>
		<td><?= $this->result['service']->service_type ?></td>
	</tr>
    <tr>
		<td>Short Code</td>
		<td><?= $this->result['service']->short_code ?></td>
	</tr>
    <tr>
		<td>Service URL</td>
		<td><?= $this->result['service']->service_endpoint ?></td>
	</tr>
    <tr>
		<td>Criteria</td>
		<td><?= $this->result['service']->criteria ?></td>
	</tr>
    <tr>
		<td>Delivery Notification URL</td>
		<td><?= $this->result['service']->delivery_notification_endpoint ?></td>
	</tr>
    <tr>
		<td>Interface Name</td>
		<td><?= $this->result['service']->interface_name ?></td>
	</tr>
    <tr>
		<td>Correlator</td>
		<td><?= $this->result['service']->correlator ?></td>
	</tr>
    <tr>
		<td>Creation Date</td>
		<td><?= $this->result['service']->created_on ?></td>
	</tr>
    <tr>
		<td>Last Updated On</td>
		<td><?= $this->result['service']->last_updated_on ?></td>
	</tr>
    <tr>
		<td>Last Updated By</td>
		<td><?= $this->result['service']->last_updated_by ?></td>
	</tr>
    
</table>