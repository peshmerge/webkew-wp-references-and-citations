document.addEventListener('DOMContentLoaded', function () {
    const Cite = require('citation-js');
    if (typeof webkewReferencesData !== 'undefined' && typeof Cite !== 'undefined') {
        var references = webkewReferencesData.references;
        var usedReferences = webkewReferencesData.usedReferences;
        try {
            // Parse the BibTeX references
            var cite = new Cite(references);

            // Filter only the used citations
            var filteredCitations = cite.data.filter(function(entry) {
                return usedReferences.includes(entry.id);
            });

            // Generate the bibliography HTML
            var bibliography = new Cite(filteredCitations).format('bibliography', {
                format: 'html',
                template: webkewReferencesData.bibliographyStyle,
                lang: 'en-US'
            });
            // Add the bibliography to the page
            jQuery('#webkew-wp-references-bibliography').append(bibliography);

            // Update citation links
            jQuery('.webkew-citation').each(function() {
                var key = jQuery(this).data('key');
                jQuery(this).attr('href', '#' + key);
            });
            jQuery('.csl-entry').each(function(){
                jQuery(this).attr('id',jQuery(this).attr("data-csl-entry-id"));
            });

            jQuery('.webkew-citation').on('click', function (){
                var key = jQuery(this).data('key');
                var bib_item_element  = jQuery('#'+key);
                jQuery('.csl-entry').removeClass('highlight');
                bib_item_element.addClass('highlight');
                setTimeout(function () {
                    bib_item_element.removeClass('highlight');
                }, 5000);
            });
        } catch (error) {
            // Add the bibliography to the page
            jQuery('#webkew-wp-references-bibliography').append(error);
        }


    }
});