<?php
use Ssg\Core\Config;
?>
<h1>Outbound Messages (Outbox) Report</h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>

<?php 
//to display data available for processing
//print_r($this->result);
?>

<h3>Report Filter:</h3>
<div class="form-group">
    <form class="form-inline">
        <input type="text" class="form-control small-control" name="service_id" id="service_id" placeholder="Service ID" value="<?=$this->service_id?>">
         <input type="text" class="form-control small-control" name="batch_id" id="batch_id" placeholder="Batch ID" value="<?=$this->batch_id?>">
        <input type="text" class="form-control" name="start_date" id="start_date" placeholder="<?=$this->start_date?>" value="<?=$this->start_date?>">
        <input type="text" class="form-control" name="end_date" id="end_date" placeholder="<?=$this->end_date?>" value="<?=$this->end_date?>">
      <button type="submit" class="btn btn-primary">Query</button>
    </form>
</div>

<table class="table table-striped">
	<thead>
    	<th>Service ID</th>
        <th>Service Name</th>
        <th>Short Code</th>
        <th>Batch ID</th>
        <th>Send Status</th>
        <th>Date</th>
        <th>Percentage (%)</th>
    </thead>
    
    <?php 
	$data = $this->result;
	$running_total = 0;
	if ($data['_recordsRetrieved']>0) {	
		foreach ($data['messages'] as $message) {
			$running_total+=$message->message_count; // increment running total
			?> 
			<tr>
				<td>
                	<?php
					if(1==1 && isset($message->calendar_date) && 'N/A' != $message->calendar_date) {
						//specify the date
						?>
						<a title="View Messages" href="<?php echo Config::get('URL'); ?>messages/outbox/?service_id=<?=$message->service_id?>&batch_id=<?=$message->batch_id?>&start_date=<?=$message->calendar_date?>&end_date=<?=$message->calendar_date?>"><?= $message->service_id ?></a></td>
						<?php
					} else {
						?>
						<a title="View Messages" href="<?php echo Config::get('URL'); ?>messages/outbox/?service_id=<?=$message->service_id?>&batch_id=<?=$message->batch_id?>&start_date=<?=$this->start_date?>&end_date=<?=$this->end_date?>"><?= $message->service_id ?></a></td>
						<?php
					}
					?>
                	
				<td><?= $message->service_name ?></td>
				<td><?= $message->sender_address ?></td>
                <td><?= $message->batch_id ?></td>
                <td>
				<?php 
					if ($message->status == 1 ){
						echo 'NEW';
					} else if ($message->status == 2 ){
						echo 'SENT';
					} else if ($message->status == 3 ){
						echo 'DELIVERED';
					} else if ($message->status == 4 ){
						echo 'FAILED';
					}
				
				?>
                </td>
				<td><?= $message->message_count ?></td>
				<td><strong><?= round(((($message->message_count)*100)/$data['_totalRecords']),2).' %' ?></strong></td>
			</tr>        
			<?php
		}
		
	} else {
		//if no records
		if ($data['_totalRecords'] == 0) {	
			?>
			<td colspan="7">No records found.</td>
			<?php
		}
	}
	
	
	//echo  $running_total;
	
	//check whether we need a additional row for services not configured
	if ( $data['_totalRecords'] != $running_total) {
		$diff = $data['_totalRecords']-$running_total;
		?>
        <tr>
            <td>N/A</td>
            <td>Others(service not configured or more records)</td>
            <td>NA</td>
            <td><?= $diff ?></td>
            <td><strong><?= round(((($diff)*100)/$data['_totalRecords']),2).' %' ?></strong></td>
        </tr>    
        <?php
	}
	
	?>
</table>