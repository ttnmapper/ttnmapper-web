
$('#device-period').datepicker({
  inputs: $('.date-range-device')
});
$('#gateway-period').datepicker({
  inputs: $('.date-range-gateway')
});
$('#experiment-period').datepicker({
  inputs: $('.date-range-experiment')
});


// Dynamically add more gateway id fiels
$(document).on('click', '.btn-gwid-add', function(e)
{
    e.preventDefault();

    var controlForm = $("#agg-gateway-list"),
        currentEntry = $(this).parents('.entry:first'),
        newEntry = $(currentEntry.clone()).appendTo(controlForm);

    newEntry.find('input').val('');
    controlForm.find('.entry:not(:last) .btn-gwid-add')
        .removeClass('btn-gwid-add').addClass('btn-gwid-remove')
        .removeClass('btn-success').addClass('btn-danger')
        .html('<span class="oi oi-minus"></span>');
}).on('click', '.btn-gwid-remove', function(e)
{
  $(this).parents('.entry:first').remove();

  e.preventDefault();
  return false;
});


// $('input[type=radio][name=agg-gateways-radio-type]').change(function() {
//     if (this.value == 'alpha') {
//       $('#agg-gateways-form').attr('action', "/alpha-shapes/");
//     }
//     else if (this.value == 'radar') {
//       $('#agg-gateways-form').attr('action', "/colour-radar/");
//     }
// });

$("#agg-gateways-btn-map").click(function(event){
  event.preventDefault();

  var tempForm = $('<form id="tempForm" method="POST" target="_blank"></form>');

  // Depending on radio, choose action
  var radioValue = $("input[type=radio][name=agg-gateways-radio-type]:checked").val();
  if (radioValue == 'alpha') {
    tempForm.attr('action', "/alpha-shapes/");
  }
  else if (radioValue == 'radar') {
    tempForm.attr('action', "/colour-radar/");
  }
  
  //agg-gateways-gateway-id
  tempForm.attr('action', tempForm.attr('action')+"?");
  $('#agg-gateways-form *').filter('#agg-gateways-gateway-id').each(function(){
      //tempForm.append('<input type="hidden" name="gateway[]" value="' + this.value + '" /> ');
      if(this.value != "") {
        tempForm.attr('action', tempForm.attr('action')+"gateway[]="+this.value+"&");
      }

  });
  
  var currentAction = tempForm.attr('action');
  tempForm.attr('action', currentAction.substring(0, currentAction.length - 1));
  console.log(tempForm.attr('action'));
  tempForm.appendTo(document.body).submit();
  $("#tempForm").remove();
});


// Example starter JavaScript for disabling form submissions if there are invalid fields
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();