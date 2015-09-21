<?php 
use Ssg\Core\Config;
?>
<span class="pull-right">
	<a class="btn btn-primary" target="_blank" href="<?php echo Config::get('URL'); ?>messages/delvryrcpts_pdf/?dest_address=<?=$this->dest_address?>&correlator=<?=$this->correlator?>&start_date=<?=$this->start_date?>&end_date=<?=$this->end_date?>" role="button">Print PDF</a>
</span>
<h1>Query Delivery Receipts</h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>

<h3>Query Filter:</h3>
<div class="form-group">
    <form class="form-inline">
        <input type="text" class="form-control" name="dest_address" id="dest_address" placeholder="Recipient" value="<?=$this->dest_address?>">
        <input type="text" class="form-control small-control" name="correlator" id="correlator" placeholder="Correlator" value="<?=$this->correlator?>">
        
        <input type="text" class="form-control" name="start_date" id="start_date" placeholder="<?=$this->start_date?>" value="<?=$this->start_date?>">
        <input type="text" class="form-control" name="end_date" id="end_date" placeholder="<?=$this->end_date?>" value="<?=$this->end_date?>">
      <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>
<table class="table table-striped">
	<thead>
    	<th>#</th>
        <th>Recipient</th>
        <th>Correlator</th>
        <th>Delivery Status</th>
        <th>SDP Timestamp</th>
		<th>Trace Unique ID</th>
        <th>Processing Time</th>
    </thead>
<?php 
$data = $this->result;
if ($data['_recordsRetrieved']>0) {	
	foreach ($data['messages'] as $message) {
		?> 
        <tr>
            <td><?=$message->id?></td>
            <td><?=$message->dest_address?></td>
            <td><?=$message->correlator?></td>
            <td><?=$message->delivery_status?></td>
            <td><?=$message->time_stamp?></td>
            <td><?=$message->trace_unique_id?></td>
			<td><?=$message->created_on?></td>
        </tr>        
        <?php
	}
	
} else {
	?>
    <td colspan="7">No records found.</td>
    <?php
}
?>
</table>

<?php 
	echo $this->markup;
?>

