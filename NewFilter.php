<?php

namespace Burburo\Api\NewBack;


use \Bitrix\Main\Context;
use \Bitrix\Main\Engine\Response\Json;
use \Bitrix\Main\Loader;
Loader::includeModule('Iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

class NewFilter {
    /** Список с переменными которые будут передоваться либо использоваться в классе
     * Пример массива который приходит
     *  {
     *      "object_realty": [],
     *      "min_price": "",
     *      "max_price": "",
     *      "min_total_space": "",
     *      "max_total_space": "",
     *      "min_floor": "",
     *      "max_floor": "",
     *      "City": []
     *  }
    */
    public $objectRealty = []; // Массив с типами объектов
    public $price = []; // Массив с ценами
    public $totalSpace = []; // Массив со значениями Общей площади
    public $floor = []; // Массив со значениями этажей
    public $city = []; // Массив с городами
    public $typeOffer; // Переменная по которой будем понимать какой тип предложения
    public $section; // Переменная по которой будем понимать по какому разделу фильтровать
    public $typeHouse = []; // Массив с типами домов
    public $hypothec; // Переменная со значением ипотеки
    public $areaHouse = []; // Массив со значениеями площади дома
    public $plotArea = []; // Массив со значениеями площади участка
    public $communications = []; // Массив с коммуникациями
    public $checkDubli = false; // Переменная в котрую пишем значение для поиска дублей
    public $arrElementCatalog = []; // Массив с коммуникациями


    /** Функция инициализации класса
     * Здесь будт использоваться функции которые запускаются по дефолту
     * либо переменные которые будут по дефолту
     * потом всё от сюда можно будет юзать во всём классе
    */
    public function __construct($typeOffer, $section, $objectRealty, $price, $totalSpace, $floor, $city, $typeHouse, $hypothec, $areaHouse, $plotArea, $communications) {
        $this->typeOffer = $typeOffer; // Записываем тип предложения
        $this->section = $section; // Записываем раздел
        $this->objectRealty = $objectRealty; // Записываем массив с типами объектов
        $this->price = $price; // Записываем массив с ценами
        $this->totalSpace = $totalSpace; // Записываем массив со значениями Общей площади
        $this->floor = $floor; // Записываем массив со значениями этажей
        $this->city = $city; // Записываем массив с городами
        $this->typeHouse = $typeHouse; // Записываем массив с типами домов
        $this->hypothec = $hypothec; // Записываем значение ипотеки
        $this->areaHouse = $areaHouse; // Записываем массив со значениеями площади дома
        $this->plotArea = $plotArea; // Записываем Массив со значениеями площади участка
        $this->communications = $communications; // Записываем массив с коммуникациями
    }

    /** Функция в которой будет логика фильтрации элементов по пришедшим данным
     * она будет фильтровать на всех типах предложений и на всех разделах
    */
    public function newFilterCatalog() {
        // $this->minPrice = $this->price['min'];
        // $this->maxPrice = $this->price['max'];

        // return $this->communications['Gas'];

        $filter = [
            '=ACTIVE' => 'Y',
            '=Type_offer_VALUE' => $this->typeOffer, // Фильтруем по переданному нам типу предложения
            '=IBLOCK_SECTION_NAME' => $this->section, // Фильтруем по переданному нам разделу
            '=OBJECT_TYPE_VALUE' => $this->objectRealty['values'], // Фильтруем по переданному нам массиву c типами объектов
            '>=PRICE_VALUE' => $this->price['min'], // Фильтруем по мин цене
            '<=PRICE_VALUE' => $this->price['max'], // Фильтруем по  макс цене
            '>=TOTAL_SPACE' => $this->totalSpace['min'], // Фильтруем по мин Общей площади
            '<=TOTAL_SPACE' => $this->totalSpace['max'], // Фильтруем по макс Общей площади
            '>=Floor_VALUE' => $this->floor['min'], // Фильтруем по мин этажей
            '<=Floor_VALUE' => $this->floor['max'], // Фильтруем по макс этажей
            '=City_VALUE' => $this->city['values'], // Фильтруем по переданному нам массиву c городами
            'House_type_VALUE' => $this->typeHouse['values'], // Фильтруем по переданному нам массиву c типами домов
            '=Ipoteka_VALUE' => $this->hypothec, // Фильтруем по переданному нам значению свойства ипотеки
            '>=TOTAL_SPACE' => $this->areaHouse['min'], // Фильтруем по мин площади дома
            '<=TOTAL_SPACE' => $this->areaHouse['max'], // Фильтруем по макс площади дома
            '>=Square_sec_VALUE' => $this->plotArea['min'], // Фильтруем по мин площади участка
            '<=Square_sec_VALUE' => $this->plotArea['max'], // Фильтруем по макс площади участка
            '=Gas_VALUE' => $this->communications['Gas'], // Фильтруем по наличию газа
            '=Water_VALUE' => $this->communications['Water'], // Фильтруем по наличию воды
            '=Sewerage_VALUE' => $this->communications['Sewerage'], // Фильтруем по наличию канализации
            '=Heating_VALUE' => $this->communications['Heating'], // Фильтруем по наличию отоплению
            '=Electricity_VALUE' => $this->communications['Electricity'], // Фильтруем по наличию электричеству
        ];

        $newFilter = [];
        foreach ($filter as $key => $fil) {
            if ($fil || $fil === "0") { 
                $newFilter[$key] = $fil;
            }
        }
        $filter = $newFilter;

        // return $filter;

        // Делаем обращение в базу данных для получения нужных нам данных
        $elements = \Bitrix\Iblock\Elements\ElementObjectsNewTable::getList([
            'select' => [
                'ID',
                'NAME',
                'ID_1C_VALUE' => 'ID_1C.VALUE',
                'OBJECT_TYPE_VALUE' => 'OBJECT_TYPE.ITEM.VALUE', // Получаем типы объектов
                'PRICE_VALUE' => 'Price.VALUE', // Получаем цену
                'TOTAL_SPACE' => 'Square.VALUE', // Получаем общую площадь
                'Living_space_VALUE' => 'Living_space.IBLOCK_GENERIC_VALUE', // Получаем жилую площадь
                'Kitchen_area_VALUE' => 'Kitchen_area.IBLOCK_GENERIC_VALUE', // Получаем площадь кухни
                'Floor_VALUE' => 'Floor.VALUE', // Получаем этажи
                'Floor_count_VALUE' => 'Floor_count.VALUE',  // Получаем этажность
                'Rooms_count_VALUE' => 'Rooms_count.IBLOCK_GENERIC_VALUE', // Получаем количество комнат
                'City_VALUE' => 'City2.VALUE', // Получаем города
                'Type_offer_VALUE' => 'Type_offer.ITEM.VALUE', // Получаем типы предложения
                'IBLOCK_SECTION_NAME' => 'IBLOCK_SECTION.NAME', // Получаем разделы
                'Remote_photos_VALUE' => 'Remote_photos.IBLOCK_GENERIC_VALUE', // Получаем ID картинки которая лежит в свойстве
                'Location_VALUE' => 'Location.VALUE', // Получаем адрес
                'Agent_name_VALUE' => 'Agent_name.VALUE', // Получаем имя агента
                'Agent_tel_VALUE' => 'Agent_tel.VALUE', // Получаем  телефон агента
                'House_type_VALUE' => 'House_type.ITEM.VALUE', // Получаем  типы домов
                'Ipoteka_VALUE' => 'Ipoteka.ITEM.VALUE', // Получаем площадь участка
                'Square_sec_VALUE' => 'Square_sec.VALUE',  // Получаем  значение свойства ипотеки
                'Gas_VALUE' => 'Gas.ITEM.VALUE', // Получаем  значение свойства газ
                'Water_VALUE' => 'Water.ITEM.VALUE', // Получаем  значение свойства вода
                'Sewerage_VALUE' => 'Sewerage.ITEM.VALUE', // Получаем  значение свойства канализации
                'Heating_VALUE' => 'Heating.ITEM.VALUE', // Получаем  значение свойства отопления
                'Electricity_VALUE' => 'Electricity.ITEM.VALUE', // Получаем  значение свойства электричества
            ],
            'filter' => $filter,
            'runtime' => [
                'IBLOCK_SECTION' => [
                    'data_type' => '\Bitrix\Iblock\SectionTable', // Подключаем runtime таблицу с разделами чтобы потом юзать её в select
                    'reference' => ['=this.IBLOCK_SECTION_ID' => 'ref.ID'],
                ],
            ],
            'order' => [
                'PRICE_VALUE' => 'ASC' // Сортируем по возрастанию цены
            ],
            // 'limit' => 3
        ])->fetchAll();

        // return $elements;

        // Делаем цикл по полученным элемента и заполняем массив
        foreach ($elements as $key => $element) {
            $this->checkDubli = false; // Сброс флага перед каждой итерацией
            // Делаем ещё 1 цикл чтобы пройтись по уже записанным данным и убрать дубли (ибо хз из-за чего, но данные которые приходят прямиком из бд, дублируются и мы можем получить 1 и тот же элемент раз 5)
            foreach ($this->arrElementCatalog as $deal) {
                // Проверяем наличие дубля в общем массиве с текущей итерацией
                if ($deal['NAME'] == $element['NAME']) {
                    $this->checkDubli = true; // Если нашли, то присваиваем переменной true чтобы по этой переменной делать пропуск дубля
                    break;
                }
            }
            // Проверяем, что переменная не true, чтобы пропустить дубль
            if (!$this->checkDubli) {
                // Это массив данных который одинаковый при любом фильтре
                $this->arrElementCatalog[$key]['PHOTO'] = \CFile::GetPath($element['Remote_photos_VALUE']); // Находим путь до картинки по её ID и записываем в общий массив
                $this->arrElementCatalog[$key]['PRICE'] = number_format($element['PRICE_VALUE'], 2, '.', ''); // Записываем цену в общий массив
                $this->arrElementCatalog[$key]['NAME'] = $element['NAME']; // Записываем название в общий массив
                $this->arrElementCatalog[$key]['ADDRES'] = $element['Location_VALUE']; // Записываем адресс в общий массив
                $this->arrElementCatalog[$key]['SHOW_PHONE_NUMBER']['Agent_name'] = $element['Agent_name_VALUE']; // Записываем имя агента в общий массив
                $this->arrElementCatalog[$key]['SHOW_PHONE_NUMBER']['Agent_tel'] = $element['Agent_tel_VALUE']; // Записываем телефон агента в общий массив
                $this->arrElementCatalog[$key]['ID_OBJECT'] = $element['ID_1C_VALUE']; // Записываем ID объекта в общий массив
                $this->arrElementCatalog[$key]['DETAIL_PAGE_URL'] = \CIBlockElement::GetByID($element['ID'])->GetNext()['DETAIL_PAGE_URL']; // Записываем ссылку на деталку в общий массив

                // Здесь делаем разделение на запись в общий массив, в зависимосте от того, какой раздел выбран
                if ($this->section == 'Вторичка') {
                    $this->arrElementCatalog[$key]['PROPERTY']['ROOMS_COUNT'] = $element['Rooms_count_VALUE']; // Записываем количество комнат в общий массив
                    $this->arrElementCatalog[$key]['PROPERTY']['TOTAL_SPACE'] = number_format($element['TOTAL_SPACE'], 2, '.', ''); // Записываем общую площадь в общий массив
                    $this->arrElementCatalog[$key]['PROPERTY']['LIVING_SPACE'] = number_format($element['Living_space_VALUE'], 2, '.', ''); // Записываем жилую площадь в общий массив
                    $this->arrElementCatalog[$key]['PROPERTY']['KITCHEN_AREA'] = number_format($element['Kitchen_area_VALUE'], 2, '.', ''); // Записываем площадь кухни в общий массив
                    $this->arrElementCatalog[$key]['PROPERTY']['FLOOR'] = number_format($element['Floor_VALUE'], 0, '.', '')." "."из"." ".number_format($element['Floor_count_VALUE'], 0, '.', ''); // Записываем этаж из скольки этажей в общий массив
                }
                if ($this->section == 'Загородная') {
                    $this->arrElementCatalog[$key]['PROPERTY']['SQUARE_SEC'] = number_format($element['Square_sec_VALUE'], 2, '.', ''); // Записываем площадь участка в общий массив
                    if ($element['Rooms_count_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['ROOMS_COUNT'] = number_format($element['Rooms_count_VALUE'], 0, '.', ''); // Записываем количество комнат в общий массив
                    }
                    if ($element['TOTAL_SPACE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['AREA_HOUSE'] = number_format($element['TOTAL_SPACE'], 2, '.', ''); // Записываем площадь дома в общий массив
                    }
                    if ($element['Floor_count_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['FLOORS'] = number_format($element['Floor_count_VALUE'], 0, '.', ''); // Записываем этажность в общий массив
                    }
                    if ($element['Gas_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['COMMUNICATIONS']['GAS'] = $element['Gas_VALUE']; // Записываем значение газа в общий массив
                    }
                    if ($element['Water_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['COMMUNICATIONS']['WATER'] = $element['Water_VALUE']; // Записываем значение воды в общий массив
                    }
                    if ($element['Sewerage_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['COMMUNICATIONS']['SEWERAGE'] = $element['Sewerage_VALUE']; // Записываем значение канализации в общий массив
                    }
                    if ($element['Heating_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['COMMUNICATIONS']['HEATING'] = $element['Heating_VALUE']; // Записываем значение отопления в общий массив
                    }
                    if ($element['Electricity_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['COMMUNICATIONS']['ELECTRICITY'] = $element['Electricity_VALUE']; // Записываем значение электричества в общий массив
                    }
                }
                if ($this->section == 'Коммерческая') {
                    if ($element['Floor_count_VALUE']) {
                        $this->arrElementCatalog[$key]['PROPERTY']['FLOORS'] = number_format($element['Floor_count_VALUE'], 0, '.', ''); // Записываем этажность в общий массив
                    }
                    if ($element['Floor_VALUE']) {
                        if ($element['Floor_count_VALUE']) {
                            $this->arrElementCatalog[$key]['PROPERTY']['FLOOR'] = number_format($element['Floor_VALUE'], 0, '.', '')." "."из"." ".number_format($element['Floor_count_VALUE'], 0, '.', ''); // Записываем этаж из скольки этажей в общий массив
                        } else {
                            $this->arrElementCatalog[$key]['PROPERTY']['FLOOR'] = number_format($element['Floor_VALUE'], 0, '.', ''); // Записываем этаж в общий массив
                        }
                    }
                }
            }
        }
        $this->arrElementCatalog = array_values($this->arrElementCatalog); // Перенумеровываем ключи массива чтобы он был нормально пронумерован
    }
}