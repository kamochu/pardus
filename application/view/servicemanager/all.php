<?php
use \Ssg\Core\Config;
?>
<span class="pull-right">
	<a class="btn btn-primary" href="<?php echo Config::get('URL'); ?>service/add/" role="button">Create New Service</a>
</span>
<h1>Manage Services</h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>
<h3>Query Filter:</h3>
<div class="form-group">
    <form class="form-inline">
        <input type="text" class="form-control" name="service_id" id="service_id" placeholder="Service ID" value="<?=$this->service_id?>">
        <input type="text" class="form-control" name="service_type" id="service_type" placeholder="Service Type" value="<?=$this->service_type?>">
        <input type="text" class="form-control" name="short_code" id="short_code" placeholder="Short Code" value="<?=$this->short_code?>">
      <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>

<table class="table table-striped table-hover">
	<thead>
    	<th>#</th>
        <th>Service Name</th>
        <th>Service ID</th>
        <th>Service Type</th>
        <th>Short Code</th>
        <th>Criteria</th>
        <th>Status</th>
        <th>Creation Date</th>
        <th>Manage Service</th>
    </thead>
<?php 
if ($this->result['_recordsRetrieved']>0) {	
	foreach ($this->result['services'] as $service) {
		?> 
        <tr>
            <td><?=$service->id?></td>
            <td><a href="<?php echo Config::get('URL'); ?>service/view/<?=$service->service_id?>"><?=$service->service_name?></a></td>
            <td><?=$service->service_id?></td>
            <td><?=$service->service_type?></td>
            <td><?=$service->short_code?></td>
            <td><?=$service->criteria?></td>
            <td>
            <?php 
				if ($service->status == 1) {
					//on
					?>
					<span class="btn btn-success  btn-xs">On</span>
					<?php
				} else {
					//off
					?>
					 <span class="btn btn-warning  btn-xs">Off</span>
					<?php
				}
			?>
            </td>
            <td><?=$service->created_on?></td>
			<?php
                if ($service->status == 1) {
                    //servive is ON, put it offline
                    ?>
			<td><a href="<?php echo Config::get('URL'); ?>service/disable/<?=$service->service_id?>"><span class="btn btn-warning  btn-xs">Disable Service</span></a></td>
                    <?php
                } else {
                    //servie  is OFF, enable 
                    ?>
			<td><a href="<?php echo Config::get('URL'); ?>service/enable/<?=$service->service_id?>"><span class="btn btn-success  btn-xs">Enable Service</span></a></td>
                    <?php
                }
            ?>
        </tr>        
        <?php
	}
	
} else {
	?>
    <td colspan="9">No records found.</td>
    <?php
}
?>
</table>

<?php
	echo $this->markup;
?>
