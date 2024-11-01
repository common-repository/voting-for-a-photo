jQuery(function($){
    $('.gallery-voting').click(function(event){
        var target = $(event.target);
        var parent = target.parents('.gallery');
        var galleryId = parent.data("id");
        var votingCount = parent.data("voting-count");
        var id = $(this).data("id");
        //alert(id);

        //$(this).text('Загружаю...'); // изменяем текст кнопки, вы также можете добавить прелоадер

        var data = {
            'action': 'calculate_votes',
            'attachment_id': id,
            'gallery_id': galleryId,
            'voting_count': votingCount,
        };
        $.ajax({
            url:photo_contest_options.votingAjaxUrl, // обработчик
            data:data, // данные
            type:'POST', // тип запроса
            success:function(response){
                if( response ) {
                    if (response.success == true) {
                        jQuery('span#gallery-voting-count-' + id).text(response.count);
                        jQuery('span#voting-icon-' + id).attr("class", response.class);
                    } else {
                        alert(response.error);
                    }
                } else {

                }
            }
        });
    });
});
