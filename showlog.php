<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/stop.php';">stop.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/showlog.php';">showlog.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/params.php';">params.php</button>
<button style="height:100px;width:200px" onclick="window.location.href = 'https://awsh3.000webhostapp.com/menu.php';">menu.php</button>

<?php
echo file_get_contents("errors.log");
?>