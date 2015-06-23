<?php
use \Ssg\Core\Config;
?>
	</div><!-- /min-height-200px -->
    <hr>
    <footer>
	    <p class="pull-right">Version: <?= Config::get('VERSION') ?></p>
    	<p><?= Config::get('COPYRIGHT')?></p>

    </footer>
	</div> <!-- /container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
     <?php 
		//include js files for date picker
		if ($this->view_filename == 'messages/inbox') {
			?>
	<script src="<?php echo Config::get('URL'); ?>js/moment/moment.min.js"></script>
    <script src="<?php echo Config::get('URL'); ?>assets/js/bootstrap-datetimepicker.js"></script>
			<?php
		}
	?>
    <script src="<?php echo Config::get('URL'); ?>js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo Config::get('URL'); ?>assets/js/ie10-viewport-bug-workaround.js"></script>
    
  </body>
</html>