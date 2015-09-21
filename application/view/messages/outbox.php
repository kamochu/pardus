<?php 
use Ssg\Core\Config;
?>
<span class="pull-right">
	<a class="btn btn-primary" target="_blank" href="<?php echo Config::get('URL'); ?>messages/outbox_pdf/?sender_address=<?=$this->sender_address?>&dest_address=<?=$this->dest_address?>&service_id=<?=$this->service_id?>&batch_id=<?=$this->batch_id?>&start_date=<?=$this->start_date?>&end_date=<?=$this->end_date?>" role="button">Print PDF</a>
</span>
<h1>Query Outbound Messages (Outbox)</h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>

<h3>Query Filter:</h3>
<div class="form-group">
    <form class="form-inline">
    	<input type="text" class="form-control" name="sender_address" id="sender_address" placeholder="Sender" value="<?=$this->sender_address?>">
        <input type="text" class="form-control" name="dest_address" id="dest_address" placeholder="Recipient" value="<?=$this->dest_address?>">
        <input type="text" class="form-control small-control" name="service_id" id="service_id" placeholder="Service ID" value="<?=$this->service_id?>">
        <input type="text" class="form-control small-control" name="batch_id" id="batch_id" placeholder="Batch ID" value="<?=$this->batch_id?>">
        <input type="text" class="form-control" name="start_date" id="start_date" placeholder="<?=$this->start_date?>" value="<?=$this->start_date?>">
        <input type="text" class="form-control" name="end_date" id="end_date" placeholder="<?=$this->end_date?>" value="<?=$this->end_date?>">
        <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>

<table class="table table-striped">
	<thead>
    	<th>#</th>
        <th>Sender</th>
        <th>Recipient</th>
        <th>Service</th>
        <th>Batch ID</th>
        <th width="25%">Message</th>
        <th>Send Status</th>
        <th>Delivery Status</th>
        <th>Processing Time</th>
    </thead>
<?php 
$data = $this->result;
if ($data['_recordsRetrieved']>0) {	
	foreach ($data['messages'] as $message) {
		?> 
        <tr>
            <td><?=$message->message_id?></td>
            <td><?=$message->sender_address?></td>
            <td><?=$message->dest_address?></td>
            <td><?=$message->service_id?></td>
            <td><?=$message->batch_id?></td>
            <td><?=$message->message?></td>
            <td><?=$message->status_desc?></td>
            <td><?=$message->delivery_status?></td>
			<td><?=$message->created_on?></td>
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
