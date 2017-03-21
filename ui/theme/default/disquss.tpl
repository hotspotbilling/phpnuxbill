{include file="sections/header.tpl"}

					<div class="row">
						<div class="col-sm-12">
							<div class="panel panel-hovered mb20 panel-default">
								<div class="panel-heading">Public Disquss</div>
								<div class="panel-body">
									<div id="disqus_thread"></div>
										<script>
											var disqus_config = function () {
											this.page.url = "https://ibnux.github.io/phpmixbill/diskusi.html";  // Replace PAGE_URL with your page's canonical URL variable
											this.page.identifier = "phpmixbill"; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
											};
											(function() { // DON'T EDIT BELOW THIS LINE
											var d = document, s = d.createElement('script');
											s.src = 'https://phpmixbill.disqus.com/embed.js';
											s.setAttribute('data-timestamp', +new Date());
											(d.head || d.body).appendChild(s);
											})();
										</script>
										<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>								
								</div>
							</div>
						</div>
					</div>

{include file="sections/footer.tpl"}
