document.addEventListener('DOMContentLoaded', function () {

    // Function to process references for a specific post
    function processReferences(postID) {
        const Cite = require('citation-js');
        const postData = window.webkewReferencesData[postID];

        if (!postData || typeof Cite === 'undefined') {
            console.error("Required data or Cite library not available for post:", postID);
            return;
        }

        try {
            // Parse the BibTeX references
            var cite = new Cite(postData.references);

            // Filter only the used citations
            var filteredCitations = cite.data.filter(function (entry) {
                return postData.usedReferences.includes(entry.id);
            });

            // Generate the bibliography HTML
            var bibliography = new Cite(filteredCitations).format('bibliography', {
                format: 'html',
                template: postData.bibliographyStyle,
                lang: 'en-US'
            });

            var bibliographyElement = jQuery('#webkew-wp-references-bibliography_' + postID);
            if (bibliographyElement.length) {
                bibliographyElement.append(bibliography);

                bibliographyElement.find('.webkew-citation').each(function () {
                    var key = jQuery(this).data('key');
                    jQuery(this).attr('href', '#' + key);
                });

                bibliographyElement.find('.csl-entry').each(function () {
                    jQuery(this).attr('id', jQuery(this).attr("data-csl-entry-id"));
                });
            }
        } catch (error) {
            console.error("Error generating bibliography for post " + postID + ": ", error);
        }
    }

    // Process references for all posts
    for (let postID in window.webkewReferencesData) {
        processReferences(postID);
    }

    // Add this global click handler for citations
    jQuery(document).on('click', '.webkew-citation', function (event) {
        event.preventDefault();
        var key = jQuery(this).data('key');
        var bib_item_element = jQuery('#' + key);
        jQuery('.csl-entry').removeClass('webkew_highlight');
        bib_item_element.addClass('webkew_highlight');
        setTimeout(function () {
            bib_item_element.removeClass('webkew_highlight');
        }, 5000);
    });
});