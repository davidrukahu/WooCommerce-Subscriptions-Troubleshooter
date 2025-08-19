<?php
/**
 * Install Test Data Generator
 * 
 * This is a simple installer script for the test data generator.
 * Upload both files to your test site and run this script once.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	// If not in WordPress, check if we're being accessed directly
	if ( ! isset( $_GET['install'] ) ) {
		die( 'Upload this file to your WordPress test site root and access it via: yoursite.com/install-test-data-generator.php?install=1' );
	}
	
	// Simple WordPress detection and loader
	$wp_config_path = dirname( __FILE__ ) . '/wp-config.php';
	if ( ! file_exists( $wp_config_path ) ) {
		die( 'WordPress installation not found. Make sure you upload this to your WordPress root directory.' );
	}
	
	// Load WordPress
	require_once( dirname( __FILE__ ) . '/wp-config.php' );
	require_once( ABSPATH . 'wp-admin/includes/admin.php' );
	
	// Security check
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You need administrator access to run this installer.' );
	}
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>WooCommerce Subscriptions Test Data Generator - Installer</title>
	<style>
		body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
		.notice { padding: 15px; margin: 20px 0; border-radius: 5px; }
		.notice-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
		.notice-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
		.notice-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
		.button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; }
		.button:hover { background: #005a87; }
		.requirements { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
		.requirement { margin: 10px 0; }
		.requirement.met { color: #155724; }
		.requirement.unmet { color: #721c24; }
		pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
	</style>
</head>
<body>
	<h1>🧪 WooCommerce Subscriptions Test Data Generator</h1>
	
	<?php
	// Check requirements
	$requirements = array(
		'WordPress' => defined( 'ABSPATH' ),
		'WooCommerce' => class_exists( 'WooCommerce' ),
		'WooCommerce Subscriptions' => class_exists( 'WC_Subscriptions' ),
		'Admin Access' => current_user_can( 'manage_options' ),
	);
	
	$all_met = true;
	foreach ( $requirements as $req => $met ) {
		if ( ! $met ) $all_met = false;
	}
	?>
	
	<div class="requirements">
		<h3>📋 Requirements Check</h3>
		<?php foreach ( $requirements as $req => $met ) : ?>
			<div class="requirement <?php echo $met ? 'met' : 'unmet'; ?>">
				<?php echo $met ? '✅' : '❌'; ?> <?php echo $req; ?>
			</div>
		<?php endforeach; ?>
	</div>
	
	<?php if ( ! $all_met ) : ?>
		<div class="notice notice-error">
			<strong>Requirements Not Met:</strong> Please install and activate WooCommerce and WooCommerce Subscriptions before proceeding.
		</div>
	<?php else : ?>
		
		<?php if ( isset( $_POST['install_generator'] ) ) : ?>
			<?php
			// Install the test data generator
			$generator_file = dirname( __FILE__ ) . '/test-data-generator.php';
			$theme_dir = get_template_directory();
			$plugins_dir = WP_PLUGIN_DIR;
			
			// Try to create as a mu-plugin (preferred)
			$mu_plugins_dir = WPMU_PLUGIN_DIR;
			if ( ! is_dir( $mu_plugins_dir ) ) {
				wp_mkdir_p( $mu_plugins_dir );
			}
			
			$target_file = $mu_plugins_dir . '/wcst-test-data-generator.php';
			
			if ( file_exists( $generator_file ) ) {
				$content = file_get_contents( $generator_file );
				
				// Add mu-plugin header
				$mu_header = "<?php\n/**\n * Plugin Name: WC Subscriptions Test Data Generator\n * Description: Generates test subscription data for troubleshooting\n * Version: 1.0.0\n */\n\n";
				$content = str_replace( '<?php', $mu_header, $content );
				
				if ( file_put_contents( $target_file, $content ) ) {
					echo '<div class="notice notice-success"><strong>Success!</strong> Test Data Generator has been installed as a mu-plugin.</div>';
					echo '<p><strong>Next Steps:</strong></p>';
					echo '<ol>';
					echo '<li>Go to your WordPress admin: <a href="' . admin_url() . '" target="_blank">' . admin_url() . '</a></li>';
					echo '<li>Navigate to <strong>WooCommerce → Test Data Generator</strong></li>';
					echo '<li>Click "Generate Test Subscription Data"</li>';
					echo '<li>Test the Subscriptions Troubleshooter with the generated data</li>';
					echo '</ol>';
				} else {
					echo '<div class="notice notice-error"><strong>Error:</strong> Could not write to mu-plugins directory. Please check file permissions.</div>';
				}
			} else {
				echo '<div class="notice notice-error"><strong>Error:</strong> test-data-generator.php file not found. Please upload both files together.</div>';
			}
			?>
		<?php else : ?>
			
			<div class="notice notice-warning">
				<strong>⚠️ Important:</strong> This tool is designed for TEST SITES ONLY. Do not run on production!
			</div>
			
			<h3>🚀 Ready to Install</h3>
			<p>This installer will set up the Test Data Generator that creates:</p>
			
			<ul>
				<li><strong>5 Test Customers</strong> (customer1@test.com through customer5@test.com)</li>
				<li><strong>3 Subscription Products</strong> (Monthly, Annual, Premium with trial)</li>
				<li><strong>15-20 Test Subscriptions</strong> with various realistic issues:</li>
				<ul>
					<li>Failed payment attempts</li>
					<li>Expired Stripe test cards</li>
					<li>Missing scheduled actions</li>
					<li>Timeline gaps and inconsistencies</li>
					<li>Different subscription statuses</li>
					<li>Payment retry scenarios</li>
				</ul>
			</ul>
			
			<h3>🔧 Stripe Test Mode Integration</h3>
			<p>The generator creates subscriptions that work with Stripe in test mode:</p>
			<ul>
				<li>Uses Stripe test customer IDs (cus_test_*)</li>
				<li>Creates test payment sources (card_test_*)</li>
				<li>Simulates real payment failures and retries</li>
				<li>Works with Stripe webhook testing</li>
			</ul>
			
			<h3>📊 What You'll Be Able to Test</h3>
			<p>After generation, you can test these scenarios with the Subscriptions Troubleshooter:</p>
			
			<pre><strong>Test Scenarios:</strong>
🔍 Search by subscription ID: Use any generated subscription ID
📧 Search by email: customer1@test.com, customer2@test.com, etc.
📈 Healthy subscriptions: Active subscriptions with clean timelines
⚠️ Payment issues: Failed cards, declined payments, retries
🔄 Action Scheduler problems: Missing or failed renewal actions
📅 Timeline gaps: Subscriptions with missing activity periods
🔄 Status transitions: On-hold, cancelled, pending-cancel subscriptions</pre>
			
			<form method="post">
				<button type="submit" name="install_generator" class="button">
					Install Test Data Generator
				</button>
			</form>
			
		<?php endif; ?>
		
	<?php endif; ?>
	
	<hr>
	<h3>🔍 Alternative Installation Methods</h3>
	
	<h4>Method 1: Manual Upload to mu-plugins</h4>
	<ol>
		<li>Upload <code>test-data-generator.php</code> to <code>/wp-content/mu-plugins/</code></li>
		<li>Rename it to <code>wcst-test-data-generator.php</code></li>
		<li>It will be automatically active</li>
	</ol>
	
	<h4>Method 2: WP-CLI (if available)</h4>
	<pre>wp eval-file test-data-generator.php
wp wcst generate-test-data</pre>
	
	<h4>Method 3: Add to functions.php temporarily</h4>
	<ol>
		<li>Copy the contents of <code>test-data-generator.php</code></li>
		<li>Paste into your theme's <code>functions.php</code> file</li>
		<li>Access WooCommerce → Test Data Generator</li>
		<li>Remove from functions.php after use</li>
	</ol>
	
	<hr>
	<p><small><strong>Security Note:</strong> Remember to remove these files from your production site. This tool includes safety checks to prevent running on production, but it's best practice to remove testing tools entirely.</small></p>
	
</body>
</html>
