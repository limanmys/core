<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<form onsubmit="talkApi(this)">
		<input id="girilenVeri" type="text" name="name">	
		<button type="submit">Ekle</button>	
	</form>

	<div id="gelencevap">
		Cevap : 
	</div>
	<script type="text/javascript">
		function talkApi(data){
			console.log(data);
			return true;
			let isim = document.getElementById("girilenVeri").value;
			var xhttp = new XMLHttpRequest();
			  xhttp.onreadystatechange = function() {
			    if (this.readyState == 4 && this.status == 200) {
			     document.getElementById("gelencevap").innerHTML = this.responseText;
			    }
			  };
			// xhttp.open("POST", "/muhterem/muhterem.php", true);
			// let data = new FormData();
			// data.append("name",isim);
			// xhttp.send(data);
		}
	</script>
</body>
</html>