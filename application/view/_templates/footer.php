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
    <script src="<?php echo Config::get('URL'); ?>js/jquery1.11.2.min.js"></script>
    <script src="<?php echo Config::get('URL'); ?>js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo Config::get('URL'); ?>assets/js/ie10-viewport-bug-workaround.js"></script>
    
    <!-- Scripts to display the date picker -->
    <script src="<?php echo Config::get('URL'); ?>js/jtable/scripts/jquery-1.6.4.min.js" type="text/javascript"></script>
    <script src="<?php echo Config::get('URL'); ?>js/jtable/scripts/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
    <script>
        $(function() {
            $( "#start_date" ).datepicker({ dateFormat: "yy-mm-dd" });
            $( "#end_date" ).datepicker({ dateFormat: "yy-mm-dd" });
        });
    </script>
  </body>
</html>