jQuery(document).ready(function($) {

  tinymce.create('tinymce.plugins.shple_plugin', {
    init : function(ed, url) {
        // Register command for when button is clicked
        ed.addCommand('shple_insert_shortcode', function() {

          // test content to ensure that no existing shoppable frames are there
          var post_content = tinyMCE.activeEditor.getContent();
          var id = prompt('Enter the ID of the frame you want to use, or leave this blank (recommended) for the frame to be chosen by associating the permalink path name for this post with a frame in the Shoppable dashboard.', null);

          if (!id && post_content.indexOf('[shoppable_frame]') !== -1) {
            alert('In order to use a second frame you must use a frame ID.');
            return false;
          }

          if (id) {
            content = '[shoppable_frame id=' + id + ']';
          }
          else {
            content = '[shoppable_frame]';
          }

          tinymce.execCommand('mceInsertContent', false, content);
        });

      // Register buttons - trigger above command when clicked
      ed.addButton('shple_button', {title : 'Insert shortcode', cmd : 'shple_insert_shortcode', image: url + '/../images/tag-grey.png' });
    },
  });

  // Register our TinyMCE plugin
  // first parameter is the button ID1
  // second parameter must match the first parameter of the tinymce.create() function above
  tinymce.PluginManager.add('shple_button', tinymce.plugins.shple_plugin);
});