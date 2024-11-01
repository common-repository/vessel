(function($) {
  var apiUrl = 'https://wzgd-central.com/api/';

  $('#btn').click(function() {
    if ($.trim($("#username").val()) === "" || $.trim($("#email").val()) === "" || $.trim($("#business").val()) === ""
    || $.trim($("#msg").val()) === "") {
      alert('You did not fill out one of the fields!');
      return false;
    }

    var emailData = {
      userid: $(this).attr('data-id'),
      name: $('#username').val(),
      email: $('#email').val(),
      account_info: $('#business').val(),
      message: $('#msg').val(),
      site_info: JSON.stringify(serverinfo.supportData)
    };

    $.post(
      apiUrl + "support/contact-us",
      emailData,
      function (data) {
        console.log(data);
      },
      'json'
    );      
  });

  $('.tablink').click(function() {
    var id = $(this).attr('data-id');
    $('.tabcontent').css('display','none');
    $('.tablink').removeClass("active");
    $(this).addClass("active");
    $('#' + id).css('display','block');
  });
  $('#vessel-tabs-default').click();
})(jQuery);



   