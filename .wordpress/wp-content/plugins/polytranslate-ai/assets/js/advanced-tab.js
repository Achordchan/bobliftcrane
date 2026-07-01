jQuery(document).ready(function ($) {
    
  //-------------------- Advanced Tab ------------//
  $("#polytranslate-ai-posts-meta-fields-table").tablesorter({
    theme: 'blue',
    widthFixed: true,
    widgets: ['filter', 'group', 'zebra'],
    sortList: [[0, 0]],
    widgetOptions: {
      filter_external: '#polytranslate-ai-posts-meta-fields-search',
      filter_columnFilters: false,
      filter_ignoreCase: false,
    }
  });

  $("#polytranslate-ai-taxonomies-meta-fields-table").tablesorter({
    theme: 'blue',
    widthFixed: true,
    widgets: ['filter', 'group', 'zebra'],
    sortList: [[0, 0]],
    widgetOptions: {
      filter_external: '#polytranslate-ai-taxonomies-meta-fields-search',
      filter_columnFilters: false,
      filter_ignoreCase: false,
    }
  });

  // Fix table sorter style loading
  $('.polytranslate-ai-table-wrapper').css('visibility', 'visible');

  $("#polytranslate-ai-taxonomies-meta-fields-table input[type='checkbox'], #polytranslate-ai-posts-meta-fields-table input[type='checkbox']").change(function () {
    $cell = $(this).closest('tr').find('td');
    if (this.checked) {
      $cell.find('.visibility-status').text('(True)');
    } else {
      $cell.find('.visibility-status').text('');
    }
    // Update table for filtering
    $(this).closest('table').trigger('update');
  });

  // Trigger change event for already checked checkboxes
  $("#polytranslate-ai-taxonomies-meta-fields-table input[type='checkbox']:checked, #polytranslate-ai-posts-meta-fields-table input[type='checkbox']:checked").trigger('change');

  
});