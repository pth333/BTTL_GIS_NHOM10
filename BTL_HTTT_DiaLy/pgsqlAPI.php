<?php 
$paPDO = initDB();
$paSRID = '4326';

function initDB()
{
    // Kết nối CSDL
    $paPDO = new PDO('pgsql:host=localhost;dbname=railway_vietnam;port=5432', 'postgres', '123456');
    return $paPDO;
}
function query($paPDO, $paSQLStr)
{
    try
    {
        // Khai báo exception
        $paPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Sử đụng Prepare 
        $stmt = $paPDO->prepare($paSQLStr);
        // Thực thi câu truy vấn
        $stmt->execute();
        
        // Khai báo fetch kiểu mảng kết hợp
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        
        // Lấy danh sách kết quả
        $paResult = $stmt->fetchAll();   
        return $paResult;                 
    }
    catch(PDOException $e) {
        echo "Thất bại, Lỗi: " . $e->getMessage();
        return null;
    }       
}
function closeDB($paPDO)
{
    // Ngắt kết nối
    $paPDO = null;
}
if (isset($_POST['functionname'])) {
    $paPoint = $_POST['paPoint'];

    $functionname = $_POST['functionname'];

    $aResult = "null";
    if ($functionname == 'getGeoVNToAjax')
        $aResult = getGeoVNToAjax($paPDO, $paSRID, $paPoint);
    else if ($functionname == 'getInfoVNToAjax')
        $aResult = getInfoVNToAjax($paPDO, $paSRID, $paPoint);
    else if ($functionname == 'getInfoRailsToAjax')
        $aResult = getInfoRailsToAjax($paPDO, $paSRID, $paPoint);
    else if ($functionname == 'getInfoStationToAjax')
        $aResult = getInfoStationToAjax($paPDO, $paSRID, $paPoint);
    else if ($functionname == 'getStationToAjax')
        $aResult = getStationToAjax($paPDO, $paSRID, $paPoint);
    else if ($functionname == 'getRailsToAjax')
        $aResult = getRailsToAjax($paPDO, $paSRID, $paPoint);

    echo $aResult;

    closeDB($paPDO);
}

// hightlight VN
function getGeoVNToAjax($paPDO, $paSRID, $paPoint)
{

    $paPoint = str_replace(',', ' ', $paPoint);
    $mySQLStr = "SELECT ST_AsGeoJson(geom) as geo from \"tinh\" where ST_Within('SRID=" . $paSRID . ";" . $paPoint . "'::geometry,geom)";
    $result = query($paPDO, $mySQLStr);
    if ($result != null) {
        // Lặp kết quả
        foreach ($result as $item) {
            return $item['geo'];
        }
    } else
        return "null";
}
// hightlight station
function getStationToAjax($paPDO, $paSRID, $paPoint)
{
    $paPoint = str_replace(',', ' ', $paPoint);
    $strDistance = "ST_Distance('" . $paPoint . "',ST_AsText(geom))";
    $strMinDistance = "SELECT min(ST_Distance('" . $paPoint . "',ST_AsText(geom))) from ga";
    $mySQLStr = "SELECT ST_AsGeoJson(geom) as geo from ga where " . $strDistance . " = (" . $strMinDistance . ") and " . $strDistance . " < 0.1";
    $result = query($paPDO, $mySQLStr);
    if ($result != null) {
        // Lặp kết quả
        foreach ($result as $item) {
            return $item['geo'];
        }
    } else
        return "null";
}

// hightlight rails
function getRailsToAjax($paPDO, $paSRID, $paPoint)
{
    $paPoint = str_replace(',', ' ', $paPoint);
    $strDistance = "ST_Distance('" . $paPoint . "',ST_AsText(geom))";
    $strMinDistance = "SELECT min(ST_Distance('" . $paPoint . "',ST_AsText(geom))) from duongray";
    $mySQLStr = "SELECT ST_AsGeoJson(geom) as geo from duongray where " . $strDistance . " = (" . $strMinDistance . ") and " . $strDistance . " < 0.1";
    $result = query($paPDO, $mySQLStr);

    if ($result != null) {
        // Lặp kết quả
        foreach ($result as $item) {
            return $item['geo'];
        }
    } else
        return "null";
}



// Truy van thong tin tinh VN
function getInfoVNToAjax($paPDO, $paSRID, $paPoint)
{

    $paPoint = str_replace(',', ' ', $paPoint);
    // $mySQLStr = "SELECT gid, name_1, ST_Area(geom) dt, ST_Perimeter(geom) as cv from \"arg_adm1\" where ST_Within('SRID=".$paSRID.";".$paPoint."'::geometry,geom)";
    $mySQLStr = "SELECT gid, name from \"tinh\" where ST_Within('SRID=" . $paSRID . ";" . $paPoint . "'::geometry,geom)";

    $result = query($paPDO, $mySQLStr);

    if ($result != null) {
        $resFin = '<table>';
        // Lặp kết quả
        foreach ($result as $item) {
            $resFin = $resFin . '<tr><td>Mã Vùng: ' . $item['gid'] . '</td></tr>';
            $resFin = $resFin . '<tr><td>Tên Tỉnh: ' . $item['name'] . '</td></tr>';
            // $resFin = $resFin . '<tr><td>Dân số: ' . $item['danso'] . ' người ' .'</td></tr>';
            // $resFin = $resFin . '<tr><td>Diện Tích: ' . $item['dientich'] . ' km2 '.'</td></tr>';
            break;
        }
        $resFin = $resFin . '</table>';
        return $resFin;
    } else
        return "null";
}

//Truy van thong tin rails
function getInfoRailsToAjax($paPDO, $paSRID, $paPoint)
{
    $paPoint = str_replace(',', ' ', $paPoint);
    $strDistance = "ST_Distance('" . $paPoint . "',ST_AsText(geom))";
    $strMinDistance = "SELECT min(ST_Distance('" . $paPoint . "',ST_AsText(geom))) from duongray";
    $mySQLStr = "SELECT gid  from duongray where " . $strDistance . " = (" . $strMinDistance . ") and " . $strDistance . " < 0.1";
    $result = query($paPDO, $mySQLStr);

    if ($result != null) {
        $resFin = '<table>';
        // Lặp kết quả
        foreach ($result as $item) {
            $resFin = $resFin . '<tr><td>G_ID: ' . $item['gid'] . '</td></tr>';
            // $resFin = $resFin . '<tr><td>Số hiệu: ' . $item['fid_rail_d'] . '</td></tr>';
            // $resFin = $resFin . '<tr><td>Chiều dài: ' . $item['chieudai'] . '</td></tr>';
            break;
        }
        $resFin = $resFin . '</table>';
        return $resFin;
    } else
        return "null";
}

// truy van thong tin san ga
function getInfoStationToAjax($paPDO, $paSRID, $paPoint)
{
    $paPoint = str_replace(',', ' ', $paPoint);
    $strDistance = "ST_Distance('" . $paPoint . "',ST_AsText(geom))";
    $strMinDistance = "SELECT min(ST_Distance('" . $paPoint . "',ST_AsText(geom))) from ga";
    $mySQLStr = "SELECT gid, ten_ga from ga where " . $strDistance . " = (" . $strMinDistance . ") and " . $strDistance . " < 0.1";
    $result = query($paPDO, $mySQLStr);

    if ($result != null) {
        $resFin = '<table>';
        // Lặp kết quả
        foreach ($result as $item) {
            $resFin = $resFin . '<tr><td>G_id: ' . $item['gid'] . '</td></tr>';
            $resFin = $resFin . '<tr><td>Tên điểm giao thông: ' . $item['ten_ga'] . '</td></tr>';
            break;
        }
        $resFin = $resFin . '</table>';
        return $resFin;
    } else
        return "null";
}
