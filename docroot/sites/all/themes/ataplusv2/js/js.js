(function($){
    $("#dashboard-settings li:eq(0) a").tab('show');

    var samalebar= $('.boxes3').width();
$('.homepage .boxes3').css('height', samalebar );


if ($(window).width() > 960) {
   $('.col-xs-2.bg-grey').each(function(){
      var parentHeight = $(this).parent().parent().parent().height();
      $(this).height(parentHeight);
});



}
else {

}

if ($(window).width() > 960) {
   $('.padd20.bg-dark-purple.white, .padd20.bg-grey.dark-blue').each(function(){

});



}
else {
   }

$('.amount').on('input', function() {
	var valuation = $('.valuation').val(); //hidden
var userAmount = $('.amount').val(); //user keyin
var equityCalc = (userAmount/valuation*100).toFixed(4); //calc
  $('.eq').html(equityCalc);
});


/*-- var minAmount = $('.atadeals-invest-amount-form .min_amount').val();
var userAmount = $('.atadeals-invest-amount-form .amount.form-text').val();
if (userAmount < minAmount) {
  $('.atadeals-invest-amount-form .js-form-submit.form-submit').attr('disabled', true);
}
else {
  $('.atadeals-invest-amount-form .js-form-submit.form-submit').attr('disabled', false);
}
--*/
$('.whybox').css('height', $('.investnowbox').height());

var samatinggi= $('#deal-page-content .col-sm-9').height();
$('#deal-page-content .tab-pane .col-sm-3.bg-grey').css('height', samatinggi );

var blogsamatinggi= $('.blog-listing .col-sm-8').height();
$('.blog-sidebar').css('height', blogsamatinggi );


$('.button-tabs #all-button').click(function(){
		$('.all-listings').addClass('blocked');
		$('.all-listings').removeClass('hidden');
		$('.ongoing-listings, .secured-listings').addClass('hidden');
		$('.ongoing-listings, .secured-listings').removeClass('blocked');
	});
	$('.button-tabs #ongoing-button').click(function(){
		$('.ongoing-listings').addClass('blocked');
		$('.ongoing-listings').removeClass('hidden');
		$('.all-listings, .secured-listings').addClass('hidden');
		$('.all-listings, .secured-listings').removeClass('blocked');
	});
	$('.button-tabs #secured-button').click(function(){
		$('.secured-listings').addClass('blocked');
		$('.secured-listings').removeClass('hidden');
		$('.ongoing-listings, .all-listings').addClass('hidden');
		$('.ongoing-listings, .all-listings').removeClass('blocked');
	});
$('.video').parent().click(function () {
    if((this).children(".video").get(0).paused){
        $(this).children(".video").get(0).play();
        $(this).children(".playpause").fadeOut();
    }else{
       $(this).children(".video").get(0).pause();
        $(this).children(".playpause").fadeIn();
    }
});

  var hash = window.location.hash;
  hash && $('ul.nav a[href="' + hash + '"]').tab('show');

  $('.nav-tabs a').click(function (e) {
    $(this).tab('show');
    var scrollmem = $('body').scrollTop() || $('html').scrollTop();
    window.location.hash = this.hash;
    $('html,body').scrollTop(scrollmem);
  });

  $("#show-modal-2").click(function(event) {
    $("#modal-invest-1").modal('hide');
    $("#modal-invest-2").modal('show');
  });

})(jQuery);
