<?php 
use Ssg\Core\Config;
?>

<h1>Setting new password for user <strong><?php echo $this->user_name; ?></strong></h1>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>
