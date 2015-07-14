<?php 
use Ssg\Core\Config;
?>
<span class="pull-right">
	<a class="btn btn-primary" target="_blank" href="<?php echo Config::get('URL'); ?>messages/subscriptions_pdf/?subscriber_id=<?=$this->subscriber_id?>&service_id=<?=$this->service_id?>&product_id=<?=$this->product_id?>&update_type=<?=$this->update_type?>&start_date=<?=$this->start_date?>&end_date=<?=$this->end_date?>" role="button">Print PDF</a>
</span>
<h1>Query Subscription Messages</h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>

<h3>Query Filter:</h3>
<div class="form-group">
    <form class="form-inline">
        <input type="text" class="form-control" name="subscriber_id" id="subscriber_id" placeholder="Subscriber" value="<?=$this->subscriber_id?>">
        <input type="text" class="form-control small-control" name="service_id" id="service_id" placeholder="Service ID" value="<?=$this->service_id?>">
        <input type="text" class="form-control small-control" name="product_id" id="product_id" placeholder="Product ID" value="<?=$this->product_id?>">
        <input type="text" class="form-control small-control" name="update_type" id="update_type" placeholder="Update Type" value="<?=$this->update_type?>">
        <input type="text" class="form-control" name="start_date" id="start_date" placeholder="<?=$this->start_date?>" value="<?=$this->start_date?>">
        <input type="text" class="form-control" name="end_date" id="end_date" placeholder="<?=$this->end_date?>" value="<?=$this->end_date?>">
      <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>
<table class="table table-striped">
	<thead>
    	<th>#</th>
        <th>Subscriber</th>
        <th>Service ID</th>
        <th>Product ID</th>
        <th>Update Type</th>
		<th>Effective Time</th>
		<th>Expiry Time</th>
        <th>Processing Time</th>
    </thead>
<?php 
$data = $this->result;
if ($data['_recordsRetrieved']>0) {	
	foreach ($data['messages'] as $message) {
		?> 
        <tr>
            <td><?=$message->id?></td>
            <td><?=$message->subscriber_id?></td>
            <td><?=$message->service_id?></td>
            <td><?=$message->product_id?></td>
            <td><?=$message->update_desc?></td>
            <td><?=$message->effective_time?></td>
            <td><?=$message->expiry_time?></td>
			<td><?=$message->created_on?></td>
        </tr>        
        <?php
	}
	
} else {
	?>
    <td colspan="8">No records found.</td>
    <?php
}
?>
</table>
<?php 
	echo $this->markup;
?>

