<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   document-management
 * @author    Team Ajency <talktous@ajency.in>
 * @license   GPL-2.0+
 * @link      http://ajency.in
 * @copyright 9-22-2014 Ajency.in
 */

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));

wp_enqueue_script('plupload-handlers');

$post_id = 0;
if ( isset( $_REQUEST['post_id'] ) ) {
	$post_id = absint( $_REQUEST['post_id'] );
	if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) )
		$post_id = 0;
}

if ( $_POST ) {
	$location = 'upload.php';
	if ( isset($_POST['html-upload']) && !empty($_FILES) ) {
		check_admin_referer('media-form');
		// Upload File button was clicked
		$id = ajdm_handle_document_upload('async-upload', $post_id);
                    if ( is_wp_error( $id ) ) { ?>
                        <div id="message" class="error"><p>Error saving Document.</p></div>
                    <?php }
                    else{ ?>
                        <div id="message" class="updated"><p>Document Added.</p></div>    
                    <?php }
	}
        
}

$title = __('Upload New Document');
$parent_file = 'upload.php';

get_current_screen()->add_help_tab( array(
'id'		=> 'overview',
'title'		=> __('Overview'),
'content'	=>
	'<p>' . __('You can upload media files here without creating a post first. This allows you to upload files to use with posts and pages later and/or to get a web link for a particular file that you can share. There are three options for uploading files:') . '</p>' .
	'<ul>' .
		'<li>' . __('<strong>Drag and drop</strong> your files into the area below. Multiple files are allowed.') . '</li>' .
		'<li>' . __('Clicking <strong>Select Files</strong> opens a navigation window showing you files in your operating system. Selecting <strong>Open</strong> after clicking on the file you want activates a progress bar on the uploader screen.') . '</li>' .
		'<li>' . __('Revert to the <strong>Browser Uploader</strong> by clicking the link below the drag and drop box.') . '</li>' .
	'</ul>'
) );
get_current_screen()->set_help_sidebar(
	'<p><strong>' . __('For more information:') . '</strong></p>' .
	'<p>' . __('<a href="http://codex.wordpress.org/Media_Add_New_Screen" target="_blank">Documentation on Uploading Media Files</a>') . '</p>' .
	'<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);

$form_class = 'media-upload-form type-form validate';

if ( get_user_setting('uploader') || isset( $_GET['browser-uploader'] ) )
	$form_class .= ' html-uploader';
?>
<div class="wrap">
	<h2><?php echo esc_html( $title ); ?></h2>

	<form enctype="multipart/form-data" method="post" action="" class="<?php echo esc_attr( $form_class ); ?>" id="file-form">

	<?php media_upload_form(); ?>

	<script type="text/javascript">
	var post_id = <?php echo $post_id; ?>, shortform = 3;
	</script>
	<input type="hidden" name="post_id" id="post_id" value="<?php echo $post_id; ?>" />
	<?php wp_nonce_field('media-form'); ?>
	<div id="media-items" class="hide-if-no-js"></div>
	</form>
</div>

