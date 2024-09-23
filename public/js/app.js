import VirtualScroller from 'https://cdn.jsdelivr.net/npm/virtual-scroller@1.13.1/dom/+esm';

// this will make sure we have 
// requests executed one by one
var queue = Promise.resolve(true);

const loading = document.querySelector('#loading');

function loadProducts() {
  loading.style.display = 'block';

  return queue = queue
  .then(() => fetch('/api/products'))
  .then((response) => response.json())
  .catch((err) => console.log(err))
  .finally(() => loading.style.display = 'none');
}

function updateProduct(id, data) {
  const options = {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  };

  return queue = queue
  .then(() => fetch('/api/products/' + id, options))
  .then((response) => response.json())
  .catch((err) => console.log(err));
}

function renderProduct(product) {
  const root = document.getElementById('product').content.querySelector('.product').cloneNode(true);

  root.querySelector('.number').textContent = product.number;
  root.querySelector('.name').textContent = product.name;
  root.querySelector('.bottlesize').textContent = product.bottlesize;
  root.querySelector('.price').textContent = product.price;
  root.querySelector('.priceGBP').textContent = product.priceGBP;

  const orderamount = root.querySelector('.orderamount');

  orderamount.textContent = product.orderamount;
  orderamount.dataset.orderamount = product.orderamount;

  root.querySelector('.add').addEventListener(
    'click', 
    () => {
      product.orderamount++;

      updateProduct(product.id, { orderamount: product.orderamount }, virtualScroller);

      orderamount.textContent = product.orderamount;
      orderamount.dataset.orderamount = product.orderamount;
    }, 
    false
  );

  root.querySelector('.clear').addEventListener(
    'click', 
    () => {
      product.orderamount = 0;

      updateProduct(product.id, { orderamount: product.orderamount }, virtualScroller);

      orderamount.textContent = product.orderamount;
      orderamount.dataset.orderamount = product.orderamount;
    }, 
    false
  );

  return root;
}

const virtualScroller = new VirtualScroller(
  document.getElementById('products'),
  [],
  renderProduct
);

const listButton = document.getElementById('list');

listButton.addEventListener(
  'click', 
  () => {
    loadProducts()
    .then((json) => virtualScroller.setItems(json));
  }, 
  false
);

const emptyButton = document.getElementById('empty');

emptyButton.addEventListener(
  'click', 
  () => virtualScroller.setItems([]), 
  false
);