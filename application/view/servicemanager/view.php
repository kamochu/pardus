<?php
use \Ssg\Core\Config;
?>
<h1>View Service</h1>
<span class="pull-right">
	<a class="btn btn-primary" href="<?php echo Config::get('URL'); ?>service/all/" role="button">All Services</a> &nbsp;
	 <?php 
		if ($this->result['service']->status == 1) {
			//on
			?>
            <a class="btn btn-warning" href="<?php echo Config::get('URL'); ?>service/disable/<?=$this->result['service']->service_id?>" role="button">Disable Service</a> &nbsp;
			<?php
		} else {
			//off
			?>
             <a class="btn btn-success" href="<?php echo Config::get('URL'); ?>service/enable/<?=$this->result['service']->service_id?>" role="button">Enable Service</a> &nbsp;
			<?php
		}
	?>
    <a class="btn btn-info" href="<?php echo Config::get('URL'); ?>service/edit/<?=$this->result['service']->service_id?>" role="button">Edit Service</a> &nbsp;
    <a class="btn btn-danger" href="<?php echo Config::get('URL'); ?>service/delete/<?=$this->result['service']->service_id?>" role="button">Delete Service</a>
</span>
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