<?php 
echo "--- Скачиваем свежую выгрузку accessories.xml\n";
//Загружаем свежую выгрузку accessories.xml для Прома
$file = 'https://admin.newtrend.team/media/export/accessories.xml';
//Находи в строке название файла
$name = substr($file,strrpos($file,'/'),strlen($file));
//Сохраняем файл
file_put_contents('../for_convert/'.$name, file_get_contents($file));

//Получаем директорию с файлами
$dir    = __DIR__;
//возвращаемся на родительскую и добавляем название папки с файлами
$dir = str_replace('\PHP', '', $dir).'\for_convert';
//Создаем массив для названий файлов
$files_array = array();
//Обходим все файлы в папке и добавляем их в массив
if ($handle = opendir($dir)) {
	while (false !== ($file = readdir($handle))) {
		if (strpos($file, '.xml')) {
			//echo "$file\n";
			array_push($files_array, $file);
		}
	}
	closedir($handle);
}
//print_r($files_array);

//Поочередно обрабатываем все файлы из директории
foreach ( $files_array as $file ) {
	//Подготавливаем название сконвертированного файла
	$file_csv = str_replace('.xml', '.csv', $file);
	//Обработка xml
	echo "--- Обработка XML\n";
	$xml = converter::handler_xml('../for_convert/'.$file );
	//Конвертация полученных данных в сsv
	echo "--- Перобразование в csv\n";
	//usleep(1000000);
	converter::converter_to_csv($xml, $file_csv);
}
echo "--- Все готово, файл(ы) сконвертирован(ы) и наход(-и, -я)тся в директории converted_files!\n";

class converter {
	//Обработка xml
	public function handler_xml($fileName) {
		
		$dom = new DOMDocument();
		$dom->load($fileName);
		
		//Заменяем названия категорий сапоставлением ID с названием
			//Находим все елементы категорий по тегу и образуем из них массиив ID -> значние
			$elementToRemove = 'category';
			$array = array();
			$matchingElements = $dom->getElementsByTagName($elementToRemove);
			$totalMatches     = $matchingElements->length;
			$elementsToDelete = array();
			for ($i = 0; $i < $totalMatches; $i++){
				$elementsToDelete[] = $matchingElements->item($i);
			}
			foreach ( $elementsToDelete as $elementToDelete ) {
				$array[$elementToDelete->getAttribute('id')]=$elementToDelete->nodeValue;
			}
			//Находим все теги категорий у товаров и сапоставляем ID категории с названием
			$categoryId = 'categoryId';
			$matchcategoryId = $dom->getElementsByTagName($categoryId);
			$totalmatchcategoryId    = $matchcategoryId->length;
			$elementsToRename = array();
			for ($i = 0; $i < $totalmatchcategoryId; $i++){
				$elementsToRename[] = $matchcategoryId->item($i);
			}
			foreach ( $elementsToRename as $elementToRename ) {
				foreach ( $array as $key=>$value ) {
					if ($key == $elementToRename->nodeValue) {
						$elementToRename->nodeValue = $value;
					}
				}
			}

		//Убираем ненужные елементы (кому как конечно=))
		$elementToRemoves = array('categories', 'currencies');
		foreach ( $elementToRemoves as $elementToRemove ) {
			$matchingElements = $dom->getElementsByTagName($elementToRemove);
			$totalMatches     = $matchingElements->length;
			$elementsToDelete = array();
			for ($i = 0; $i < $totalMatches; $i++){
				$elementsToDelete[] = $matchingElements->item($i);
			}
			foreach ( $elementsToDelete as $elementToDelete ) {
				$elementToDelete->parentNode->removeChild($elementToDelete);
			}
		}
		
		//Удаляем атрибуты, которые нам не нужны (так как ID это SKU, а available и selling_type в этой выгрузке имеет только одно значение)
		$attributes = array('id','available','selling_type');
		$elementToRemoves = 'offer';
		$matchingElements = $dom->getElementsByTagName($elementToRemoves);
		$totalMatches     = $matchingElements->length;
		$elementsToDelete = array();
		for ($i = 0; $i < $totalMatches; $i++){
			$elementsToDelete[] = $matchingElements->item($i);
		}
		foreach ( $elementsToDelete as $elementToDelete ) {
			foreach ( $attributes as $attribute ) {
				$elementToDelete->removeAttribute($attribute);
			}
		}

		//Преоборазовываем обратно в XML и одаем надальнейшуюю ковертацию
		return $xml = simplexml_import_dom($dom);
		//Также можно сохранить получившийся файл
		//$dom->save($fileName);
    }
    public function converter_to_csv($xml, $file_csv) {
		//$xml = simplexml_load_file($fileName);
		$outstream = fopen('../converted_files/'.$file_csv,'w');
		$header=1;
		foreach($xml as $det){
			foreach($det as $details2){
				foreach($details2 as $details){
					$test = get_object_vars($details);
					//print_r($test);
					
					//Выводим заголовки (В данном случае в XML у нас это сами теги), выводим с учловием только первый иначе перед каждым елементов будет строка заголовка
					$key_array = array();
					if($header<=1){
						//fputcsv($outstream,array_keys(get_object_vars($details)));
						foreach($test as $key=>$detail){
							//echo($key);
							//$key = iconv("utf-8", "windows-1251", $key);
							array_push($key_array, $key);
						}
						$header++;
						fputcsv($outstream,$key_array);
					}
					
					//Создаем массив в который будем записывать значения
					$stack = array();
					
					//Начинаем записывать заначения
					foreach($test as $tes){
						if (!is_array($tes)) {
							//Очищаем данные от лишних тегов
							//$tes = str_replace(',', ',', $tes);
							$tes = str_replace(
								array('<p>','</p>',
										//'<ul>','</ul>',
										//'<li>','</li>'
									),
								'', $tes);
							//echo $tes;
							//$tes = iconv("utf-8", "windows-1251", $tes);
							array_push($stack, $tes);
						} else {
							$params = implode("; ", str_replace(',', ',', $tes));
							//echo $params;
							//$params = iconv("utf-8", "windows-1251", $params);
							array_push($stack, $params);
						}
						//echo ',';
					}
					//echo '</br></br>';
					fputcsv($outstream,$stack);
				}
			}
		}
		//foreach($xml as $k=>$details){print_r($stack);}
		fclose($outstream);
    }
}
?>