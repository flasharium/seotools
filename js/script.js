var currentZone = null;

$.fn.clearClasses = function() {
  $(this).removeClass('alert-info')
    .removeClass('alert-danger')
    .removeClass('alert-warning')
    .removeClass('alert-success');

  return $(this);
}


$(document).ready(function() {
  var mapZone = $('.JS-MapDropZone').eq(0),
      dbZone = $('.JS-BaseDropZone').eq(0);

  if (typeof(window.FileReader) == 'undefined') {
    mapZone.find('span').text('Не поддерживается браузером!');
    mapZone.removeClass('alert-info').addClass('alert-danger');
    dbZone.find('span').text('Не поддерживается браузером!');
    dbZone.removeClass('alert-info').addClass('alert-danger');
  }

  addDragListener(mapZone);
  addDragListener(dbZone);

});


function addDragListener(elem){
  elem[0].ondragover = function() {
    elem.removeClass('alert-info').addClass('alert-success');
    currentZone = elem;
    return false;
  };

  elem[0].ondragleave = function() {
    elem.removeClass('alert-success').addClass('alert-info');
    currentZone = null;
    return false;
  };

  elem[0].ondrop = function(event) {
    event.preventDefault();
    elem.clearClasses().addClass('alert-warning');
    currentZone = elem;

    var file = event.dataTransfer.files[0],
        maxFileSize = 50000000;

    if (file.name.replace(/^.*\./, '') != currentZone.data('extension')) {
      elem.find('span').text('Неправильный тип файла!');
      elem.clearClasses().addClass('alert-danger');
      return false;
    }

    if (file.name > maxFileSize) {
      elem.find('span').text('Файл слишком большой!');
      elem.removeClass('alert-warning').addClass('alert-danger');
      return false;
    }

    var xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', uploadProgress, false);
    xhr.onreadystatechange = stateChange;
    xhr.open('POST', '/upload.php?r='+Math.random(), true);
    xhr.setRequestHeader('X-FILE-NAME', file.name);
    var formData = new FormData();
    formData.append("file", file);
    xhr.send(formData);
  };
}

function uploadProgress(event) {
  if (!currentZone)
    return;

  var percent = parseInt(event.loaded / event.total * 100);
  currentZone.find('.JS-ProgressBar').removeClass('hidden').find('.JS-ProgressBar-elem').css({width: percent + '%'});
}

function stateChange(event) {
  if (!currentZone)
    return;

  if (event.target.readyState == 4) {
    currentZone.find('.JS-ProgressBar').addClass('hidden');
    if (event.target.status == 200) {
      currentZone
        .clearClasses()
        .addClass('alert-success')
        .find('span').text('Загрузка успешно завершена!');
      currentZone.find('input').eq(0).val($.parseJSON(event.target.response).path);
    } else {
      currentZone
        .clearClasses()
        .addClass('alert-danger')
        .find('span').text('Произошла ошибка!');
    }
  }
}

