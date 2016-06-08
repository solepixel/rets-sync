<div class="wrap">

	<style type="text/css">
		form p label span {
			display: inline-block;
			width:200px;
		}
		.required {
			color:#F00;
		}
	</style>

	<h1>RETS Sync Database Settings</h1>

	<form method="post" action="">
		<h3>Database Credentials <b class="required">*</b></h3>
		<p>
			<label><input type="radio" name="<?php echo $key; ?>[credentials]" value="wpdb" class="credentials-type"<?php if( $credentials == 'wpdb' ) echo ' checked="checked"'; ?>> Use WordPress Database Connection</label> &nbsp;
			<label><input type="radio" name="<?php echo $key; ?>[credentials]" value="custom" class="credentials-type"<?php if( $credentials == 'custom' ) echo ' checked="checked"'; ?>> Custom Database Connection</label>
		</p>

		<div class="custom-db"<?php if( $credentials != 'custom' ) echo ' style="display:none;"'; ?>>
			<p>
				<label><span>Hostname: <b class="required">*</b></span> <input type="text" name="<?php echo $key; ?>[hostname]" value="<?php echo $hostname; ?>" /></label><br />
				<label><span>Username: <b class="required">*</b></span> <input type="text" name="<?php echo $key; ?>[username]" value="<?php echo $username; ?>" /></label><br />
				<label><span>Password:</span> <input type="password" name="<?php echo $key; ?>[password]" /> (only enter when setting/changing, <b class="required">*</b> required when setting up)</label><br />
				<label><span>Database: <b class="required">*</b></span> <input type="text" name="<?php echo $key; ?>[database]" value="<?php echo $database; ?>" /></label>
			</p>
		</div>

		<hr />

		<div><strong>Data Table</strong></div>
		<p>
			<label><span>Data Table Name: <b class="required">*</b></span> <input type="text" name="<?php echo $key; ?>[data_table]" value="<?php echo $data_table; ?>" /></label><br />
			<label><span>Unique Identifier Column: <b class="required">*</b></span> <input type="text" name="<?php echo $key; ?>[unique_data]" value="<?php echo $unique_data; ?>" /></label><br />
			<label><span>MLS Number Column:</span> <input type="text" name="<?php echo $key; ?>[mls_field]" value="<?php echo $mls_field; ?>" /></label>
		</p>

		<hr />

		<h3>Images</h3>

		<p><label><input type="checkbox" name="<?php echo $key; ?>[images][]" value="db" class="images-source"<?php if( in_array( 'db', $images ) ) echo ' checked="checked"'; ?>> Look for Images in Database Table</label></p>

		<p class="images-db"<?php if( ! in_array( 'db', $images ) ) echo ' style="display:none;"'; ?>>
			<label><span>Image Table Name:</span> <input type="text" name="<?php echo $key; ?>[image_table]" value="<?php echo $image_table; ?>" /></label><br />
			<label><span>Unique Identifier Column:</span> <input type="text" name="<?php echo $key; ?>[unique_image]" value="<?php echo $unique_image; ?>" /></label>
		</p>

		<p><label><input type="checkbox" name="<?php echo $key; ?>[images][]" value="ftp" class="images-source"<?php if( in_array( 'ftp', $images ) ) echo ' checked="checked"'; ?>> Look for Images via FTP server</label></p>

		<p class="images-ftp"<?php if( ! in_array( 'ftp', $images ) ) echo ' style="display:none;"'; ?>>
			<label><span>Hostname:</span> <input type="text" name="<?php echo $key; ?>[image_host]" value="<?php echo $image_host; ?>" /></label> <label>Port: <input type="text" name="<?php echo $key; ?>[image_port]" value="<?php echo $image_port; ?>" /></label><br />
			<label><span>Username:</span> <input type="text" name="<?php echo $key; ?>[image_user]" value="<?php echo $image_user; ?>" /></label><br />
			<label><span>Password:</span> <input type="password" name="<?php echo $key; ?>[image_pass]" /> (only enter when setting/changing, <b class="required">*</b> required when setting up)</label><br />
			<label><span>Path to Image Folder:</span> <input type="text" name="<?php echo $key; ?>[image_path]" value="<?php echo $image_path; ?>" /></label><br />
			<label><span>Image Prefix:</span> <input type="text" name="<?php echo $key; ?>[image_prefix]" value="<?php echo $image_prefix; ?>" /></label>
		</p>

		<p><label><input type="checkbox" name="<?php echo $key; ?>[images][]" value="url" class="images-source"<?php if( in_array( 'url', $images ) ) echo ' checked="checked"'; ?>> Look for Images on Public URL</label></p>

		<p class="images-url"<?php if( ! in_array( 'url', $images ) ) echo ' style="display:none;"'; ?>>
			<label><span>Base URL:</span> <input type="text" name="<?php echo $key; ?>[image_url]" value="<?php echo $image_url; ?>" /></label><br />
			<label><span>Basic Authentication Username:</span> <input type="text" name="<?php echo $key; ?>[image_url_user]" value="<?php echo $image_url_user; ?>" /></label><br />
			<label><span>Basic Authentication Password:</span> <input type="password" name="<?php echo $key; ?>[image_url_pass]" value="" /> (only enter when setting/changing)</label>
		</p>

		<hr />

		<h3>Sync Settings</h3>

		<p><label><span>Sync Method:</span> <select name="<?php echo $key; ?>[enabled_sync]">
			<option value="new_and_updated"<?php if( $enabled_sync == 'new_and_updated' ) echo ' selected="selected"'; ?>><?php _e( 'New &amp; Updated (runs every 30 minutes)' ); ?></option>
			<option value="images_only"<?php if( $enabled_sync == 'images_only' ) echo ' selected="selected"'; ?>><?php _e( 'Images Only (runs every hour)' ); ?></option>
			<option value="details_only"<?php if( $enabled_sync == 'details_only' ) echo ' selected="selected"'; ?>><?php _e( 'Details/Meta Only (runs every hour)' ); ?></option>
			<option value="active"<?php if( $enabled_sync == 'active' ) echo ' selected="selected"'; ?>><?php _e( 'Active (runs every 4 hours)' ); ?></option>
			<option value="all"<?php if( $enabled_sync == 'all' ) echo ' selected="selected"'; ?>><?php _e( 'All (runs every 30 Days)' ); ?></option>
		</select></label></p>

		<p><label><input type="checkbox" name="<?php echo $key; ?>[disabled]" value="disabled"<?php if( $disabled ) echo ' checked="checked"'; ?> /> Disable Sync</label></p>

		<p><?php echo $nonce; ?>
		<button type="submit" class="button button-primary button-large">Save Settings</button></p>
	</form>

	<p>&nbsp;</p>

	<h3>Sync Information</h3>

	<p>There are currently <strong class="queue-total"><?php echo number_format( $queue_total ); ?></strong> <?php echo _n( 'job', 'jobs', $queue_total ); ?> in the queue.</p>

	<?php if( $last_sync ) : ?>
		<p>The last sync was started: <strong class="last-sync"><?php echo $last_sync; ?></strong></p>
	<?php endif; ?>

<?php /*
	<h3>Sync Log</h3>
	<?php $log = get_option( '_rets_sync_log' );
	if( ! $log )
		echo 'Nothing to display.';
	else
		echo $log; ?>

*/ ?>

</div>
