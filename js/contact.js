(function($){
    setTimeout(function(){

        const ENDPOINT = 'https://aa6wmtxxs3.execute-api.us-west-2.amazonaws.com/askaquestion';
        const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];

        function suggest (query, populateResults) {
            var settings = {
                "url": ENDPOINT+"?q="+query+"*&containerType=14&containerID=1&wholeCommunityScope=true&numResults=5&displayThreadResults=false&displayAnsweredQuestionResults=false&displayDocumentResults=false&displayQuestionsOnly=true&q=how*",
                "method": "GET",
                "timeout": 0,
                "headers": {
                  "x-j-token": "no-user",
                  "accept": "application/json, text/javascript, */*; q=0.01",
                  "accept-language": "en-US,en;q=0.9",
                  "cache-control": "no-cache",
                  "pragma": "no-cache",
                },
              };
              
              $.ajax(settings).done(function (response) {
                var results = [];
                var i = 0;
                for (const result of response.results) { 
                  results.push(result)
                  i++;
                  if(i>=3) {
                    break;
                  }
                }
                populateResults(results);
                $('.similar-asked-questions').addClass('show');
                $('.dont-see-question').addClass('show')
              });

            }

            accessibleAutocomplete({
            element: document.querySelector('.webform-component--your-inquiry--what-is-your-question'),
            id: 'edit-submitted-your-inquiry-what-is-your-question', // To match it to the existing <label>.
            source: suggest,
            placeholder: 'Ex. Where can I find Vietnam Hospital Records?',
            templates:{
                inputValue: function(){

                },
                suggestion: function(suggestion) {
                  var date = new Date(suggestion.created);
                  const result = '<div class="answer"><a target="_blank" href="'+suggestion.objectURL+'">'+suggestion.subject+'</a><div class="asked">Asked: '+monthNames[date.getMonth()]+' '+date.getDate()+', '+date.getFullYear()+'</div></div>'
                  return result;
                }
              }
            })

          var autocompleteContainer = $('.autocomplete__wrapper');
          autocompleteContainer.append('<h3 class="similar-asked-questions">Similar questions already asked:</h3>');
          $('.autocomplete__input').keyup(function(){
            if(!$('.autocomplete__input').val()) {
              $('.similar-asked-questions').removeClass('show');
              $('.dont-see-question').removeClass('show');
              $('.tell-us-more').removeClass('show')
              $('.would-you-like-a-response').removeClass('show')
            }
          });

          $('.dont-see-question .ask-nara').click(function(e){
            e.preventDefault();
            $('.tell-us-more').addClass('show')
            $('.would-you-like-a-response').addClass('show');
            $('#edit-submitted-your-inquiry-response-tell-us-more').val($('.autocomplete__input').val())
          })

          $('.form-radio').on('change', function() {
            $('.similar-asked-questions').removeClass('show');
            $('.dont-see-question').removeClass('show');
            $('.tell-us-more').removeClass('show')
            $('.would-you-like-a-response').removeClass('show');
            $('.similar-asked-questions').removeClass('show');
            $('.autocomplete__input').val('')
            $('.autocomplete__menu').removeClass('autocomplete__menu--visible').addClass('autocomplete__menu--hidden');
            
          });

          $('.ask-hh').on('click', function(e){
            e.preventDefault();
            var url = 'https://historyhub.history.gov/discussion/create.jspa?containerType=14&containerID=2011&question=true&subject='+$('.autocomplete__input').val();
            window.open(url);
          })
          
    }, 2000)
})(jQuery);