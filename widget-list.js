(function() {
  if (window.eeposEventsListWidgetInitialized) return;
  window.eeposEventsListWidgetInitialized = true;

  var animDuration = 150;

  /**
   * Finds the closest element matching the given class in the given element and its parents
   * @param {Element} elem
   * @param {string} theClass
   * @returns {Element|undefined}
   */
  function findClosestElem(elem, theClass) {
    if (!elem) return;

    var cursor = elem;
    do {
      if (cursor.classList && cursor.classList.contains(theClass)) break;
    } while (cursor = cursor.parentNode);

    return cursor;
  }

  /**
   * Expands/opens an event to display more details about it.
   * Passes CSS variables for animation control.
   * @param {Element} eventElem
   */
  function openEvent(eventElem) {
    eventElem.classList.add('is-open');

    var infoElem = eventElem.querySelector('.event-info');

    infoElem.style.display = 'block';
    var targetSize = infoElem.clientHeight;
    infoElem.style.display = '';

    infoElem.setAttribute('style', '--anim-duration: ' + animDuration + 'ms; --anim-target-height: ' + targetSize + 'px');
    infoElem.classList.add('is-opening');

    setTimeout(function() {
      infoElem.classList.remove('is-opening');
      infoElem.classList.add('is-open');
      infoElem.setAttribute('style', '');
    }, animDuration);
  }

  /**
   * Collapses/closes an event to hide its extra details.
   * Passes CSS variables for animation control.
   * @param {Element} eventElem
   */
  function closeEvent(eventElem) {
    eventElem.classList.remove('is-open');

    var infoElem = eventElem.querySelector('.event-info');

    var origSize = infoElem.clientHeight;
    infoElem.setAttribute('style', '--anim-duration: ' + animDuration + 'ms; --anim-original-height: ' + origSize + 'px');
    infoElem.classList.remove('is-open');
    infoElem.classList.add('is-closing');

    setTimeout(function() {
      infoElem.classList.remove('is-closing');
      infoElem.setAttribute('style', '');
    }, animDuration);
  }

  /**
   * Closes/collapses all other events except the one passed in
   * @param {Element} eventElem
   */
  function closeOthers(eventElem) {
    var openEvents = document.querySelectorAll('.eepos-events-list-widget .event.is-open');
    openEvents.forEach(function(openEventElem) {
      if (openEventElem === eventElem) return;
      closeEvent(openEventElem);
    });
  }

  // When an event header is clicked, toggle whether the event is open/expanded or not
  // When an event is expanded by clicking on the header, other expanded events are collapsed
  document.addEventListener('click', function(ev) {
    var clickedEventHeader = findClosestElem(ev.target, 'eepos-events-list-widget-event-header');
    if (!clickedEventHeader) return;

    var eventElem = findClosestElem(clickedEventHeader, 'event');

    if (eventElem.classList.contains('is-open')) {
      closeEvent(eventElem);
    } else {
      closeOthers(eventElem);
      openEvent(eventElem);
    }
  });
})();