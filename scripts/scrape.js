const url = 'https://www.alko.fi/valikoimat-ja-hinnasto/hinnasto';
const outputPath = './storage/downloads';
const { setTimeout } = require('node:timers/promises');
const puppeteer = require('puppeteer-extra');
const { win32 } = require('node:path');
const { createCursor } = require('ghost-cursor');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs');
const path = require('path');

const getMostRecentFile = (dir) => {
  const files = orderRecentFiles(dir);

  return files.length ? files[0] : undefined;
};

const orderRecentFiles = (dir) => {
  return fs.readdirSync(dir)
    .filter((file) => fs.lstatSync(path.join(dir, file)).isFile())
    .map((file) => ({ file, mtime: fs.lstatSync(path.join(dir, file)).mtime }))
    .sort((a, b) => b.mtime.getTime() - a.mtime.getTime());
};

puppeteer.use(StealthPlugin());

puppeteer.launch({ 
  headless: true,
  args: [
    '--no-sandbox',
    '--disable-setuid-sandbox'
  ]
})
.then(async browser => {
  const now = Date.now();
  const page = await browser.newPage();
  const cursor = createCursor(page);

  await page.goto(url);
  await page.waitForSelector('#onetrust-accept-btn-handler');

  page._client().send('Browser.setDownloadBehavior', {
    behavior: 'allow', 
    downloadPath: win32.resolve(win32.normalize(outputPath))
  });

  page._client().on('Page.downloadProgress', e => {
    if (e.state === 'completed' || e.state === 'canceled') {
      const file = getMostRecentFile(outputPath);

      if (file.mtime.getTime() > now) {
        console.log(win32.resolve(win32.normalize(outputPath + '/' + file.file)));
      } else {
        console.error('Could not download Alko dataset!');
      }
      browser.close();
    }
  });

  let links = await page.$$('#onetrust-accept-btn-handler');

  await links[0].click();

  // let's wait for popup to close
  await setTimeout(1000);

  links = await page.$$('a[href$=".xlsx"]');

  await links[0].click();
});