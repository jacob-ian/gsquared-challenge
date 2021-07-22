let faqs = document.getElementsByClassName("easy-faq");

Array.from(faqs).forEach((faq, i) => {
  if (i === 0) {
    faq.classList.add("opened");
  }

  const title = faq.querySelector(".easy-faq-title");

  title.addEventListener("click", onFaqTitleClick);
});

function onFaqTitleClick(event) {
  const target = event.target;
  const parent = target.parentNode;
  parent.classList.toggle("opened");
}
