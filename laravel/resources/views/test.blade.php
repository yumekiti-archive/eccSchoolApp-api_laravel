<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
  </head>
  <body>
    <div id="body"></div>
    <script>
      const body = document.getElementById('body');
      fetch("/api/news", {
        method: "POST",
        headers : new Headers({"Content-type": "application/json"}),
        body: JSON.stringify({id: "piyo", pw: "hogehoge"})
      })
      .then(res => res.json())
      .then(data => {
        console.log(data)
        body.textContent = JSON.stringify(data)
      })
    </script>
  </body>
</html>