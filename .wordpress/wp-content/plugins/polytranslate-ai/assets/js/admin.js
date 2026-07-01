jQuery(document).ready(function ($) {

  // Single translation handler
  $(document).on('click', '.polytranslate-ai-translate-link', function (e) {
    e.preventDefault();
    const $link = $(this);

    $.ajax({
      url: $link.attr('href') + '&translate=true',
      beforeSend: function () {
        $('.polytranslate-ai-loader-overlay-container').show();
      },
      success: function (response) {
        $('.polytranslate-ai-loader-overlay-container').hide();
        window.location.reload();
      },
      error: function (xhr, status, error) {
        $('.polytranslate-ai-loader-overlay-container').hide();
        // Extract error message
        let errorMessage = "Translation failed: Unknown error";
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        } else if (xhr.responseText) {
          const plainText = xhr.responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
          if (plainText) {
            errorMessage = `Translation failed: HTTP ${xhr.status || status} - ${plainText.slice(0, 260)}`;
          }
        } else if (error) {
          errorMessage = `Translation failed: ${error}`;
        }

        // Show alert
        alert(errorMessage);
      }
    });
  });

  // AI Engine selection handler
  const selectedEngine = $('#polytranslate_ai_api_form_table #polytranslate_ai_api_engine');
  const initial = selectedEngine.val();

  $(`#polytranslate_ai_api_form_table .section[data-section='${initial}'] input.required`).attr('required', 'required'); // Add required attribute
  $(`#polytranslate_ai_api_form_table .section[data-section='${initial}']`).show();

  selectedEngine.on('change', function () {
    const selected = $(this).val();
    $('#polytranslate_ai_api_form_table .section input.required').removeAttr('required');
    $(`#polytranslate_ai_api_form_table .section[data-section='${selected}'] input.required`).attr('required', 'required');
    $('#polytranslate_ai_api_form_table .section').hide();
    $(`#polytranslate_ai_api_form_table .section[data-section='${selected}']`).show();
  });

  // Update Range Input Value
  $('#polytranslate_ai_api_form_table input[type="range"]').on('input', function () {
    const value = $(this).val();
    $(this).next('.range-value').text(value);
  });



});


document.addEventListener('alpine:init', () => {
  Alpine.data('ptaiApiTest', () => ({
    nonce: ptaiData.nonce,

    async testApiConnection(event) {
      const button = event.currentTarget;
      const provider = button.dataset.provider;
      const td = button.closest('td');
      const form = button.closest('form');
      const keyInput = td.querySelector('input[type="password"]');

      if (!keyInput?.value.trim()) {
        alert('Please enter your API key first');
        return;
      }

      const apiKey = keyInput.value.trim();
      const resultSpan = td.querySelector('.ptai-test-key-result');

      button.disabled = true;
      resultSpan.style.display = 'inline-block';
      resultSpan.innerHTML = '<span style="color:#607d8b;">Testing...</span>';

      const formData = new FormData();
      formData.append('action', 'ptai_test_api_connection');
      formData.append('nonce', this.nonce);
      formData.append('provider', provider);
      formData.append('api_key', apiKey);
      if (provider === 'OpenAI') {
        const baseUrlInput = form?.querySelector('#polytranslate_openai_base_url');
        const modelInput = form?.querySelector('#polytranslate_openai_model');
        formData.append('base_url', baseUrlInput?.value?.trim() || '');
        formData.append('model', modelInput?.value?.trim() || '');
      }

      try {
        const res = await fetch(ajaxurl, {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          resultSpan.innerHTML = `<span style="color:green;">✅ ${data.data}</span>`;
          button.disabled = false;
        } else {
          resultSpan.innerHTML = `<span style="color:red;">❌ ${data.data}</span>`;
          button.disabled = false;
        }
      } catch (err) {
        resultSpan.innerHTML = '<span style="color:red;">❌ Network error</span>';
        button.disabled = false;
      }
    }
  }));
});

