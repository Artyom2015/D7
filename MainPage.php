<?php

namespace Burburo\Api\NewBack;


use \Bitrix\Main\Context;
use \Bitrix\Main\Engine\Response\Json;
use \Bitrix\Main\Loader;
Loader::includeModule('Iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

class MainPage {
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
    public $object_realty = []; // Массив с типами объектов
    public $Price = []; // Массив с ценами
    public $min_price; // Переменная в которой хранится минимальная цена
    public $max_price; // Переменная в которой хранится максимальная цена
    public $total_space = []; // Массив со значениями Общей площади
    public $min_total_space; // Переменная в которой хранится минимальная общая площадь
    public $max_total_space; // Переменная в которой хранится максимальная общая площадь
    public $floor = []; // Массив со значениями этажей
    public $min_floor; // Переменная в которой хранится минимальное количество этажей
    public $max_floor; // Переменная в которой хранится максимальная количество этажей
    public $City = []; // Массив с городами
    public $typeOffer; // Переменная по которой будем понимать какой тип предложения
    public $Section; // Переменная по которой будем понимать по какому разделу фильтровать
    public $GreatDeals; // Переменная для нахождения элементов для выгодных предложений
    public $ArrGreatDeals = []; // Массив с выгодными предложениями
    public $checkDubli = false; // Переменная в котрую пишем значение для поиска диблей


    /** Функция инициализации класса
     * Здесь будт использоваться функции которые запускаются по дефолту
     * либо переменные которые будут по дефолту
     * 
    */
    public function __construct($typeOffer, $Section, $GreatDeals) {
        $this->typeOffer = $typeOffer; // Записываем тип предложения в заранее созданную переменную, чтобы потом юзать её во всём классе
        $this->Section = $Section; // Записываем раздел в заранее созданную переменную, чтобы потом юзать её во всём классе
        $this->GreatDeals = $GreatDeals; // Записываем выгодные предложения в заранее созданную переменную, чтобы потом юзать её во всём классе
    }

    /** Функция для заполнения инпутов из имеющихся данных у товаров
     * Пример массива который будет уже с данными
     *  {
     *      "object_realty": [], //нужно сделать обрезание названию чтобы отдавалось 1к, 2к и т.д.
     *      "min_price": "",
     *      "max_price": "",
     *      "min_total_space": "",
     *      "max_total_space": "",
     *      "min_floor": "",
     *      "max_floor": "",
     *      "City": []
     *  }
    */
    public function InputFilter() {
        // Делаем обращение в базу данных для получения нужнех нам данных
        $elements = \Bitrix\Iblock\Elements\ElementObjectsNewTable::getList([
            'select' => [
                'ID',
                'NAME',
                'OBJECT_TYPE_VALUE' => 'OBJECT_TYPE.ITEM.VALUE', // Получаем типы объектов
                'PRICE_VALUE' => 'Price.IBLOCK_GENERIC_VALUE', // Получаем цену
                'TOTAL_SPACE' => 'Square.IBLOCK_GENERIC_VALUE', // Получаем общую площадь
                'Floor_VALUE' => 'Floor.IBLOCK_GENERIC_VALUE', // Получаем этажи
                'City_VALUE' => 'City2.VALUE', // Получаем города
                'Type_offer_VALUE' => 'Type_offer.ITEM.VALUE', // Получаем типы предложения
                'IBLOCK_SECTION_NAME' => 'IBLOCK_SECTION.NAME', // Получаем разделы
            ],
            'filter' => [
                '=ACTIVE' => 'Y',
                '=Type_offer_VALUE' => $this->typeOffer, // Фильтруем по переданному нам типу предложения
                '=IBLOCK_SECTION_NAME' => $this->Section, // Фильтруем по переданному нам разделу
            ],
            'runtime' => [
                'IBLOCK_SECTION' => [
                    'data_type' => '\Bitrix\Iblock\SectionTable', // Подключаем runtime таблицу с разделами чтобы потом юзать её в select
                    'reference' => ['=this.IBLOCK_SECTION_ID' => 'ref.ID'],
                ],
            ],
        ])->fetchAll();

        // Делаем цикл по полученным элементам
        foreach ($elements as $element) {
            $this->object_realty[] = $element['OBJECT_TYPE_VALUE']; // Записываем в масив все типы объектов
            $this->Price[] = $element['PRICE_VALUE']; // Записываем в масив все цены 
            $this->total_space[] = $element['TOTAL_SPACE']; // Записываем в масив все значения общей площади
            $this->floor[] = $element['Floor_VALUE']; // Записываем в масив все значения этажей
            // Пропускаем пустые поля у городов
            if ($element['City_VALUE']) {
                $this->City[] = $element['City_VALUE']; // Записываем в масив все города
            }
        }
        $this->object_realty = array_unique($this->object_realty); // Убираем дубли из итогового массива
        $this->City = array_unique($this->City); // Убираем дубли из итогового массива
        $this->City = array_values($this->City); // Перенумеровываем ключи массива чтобы он выглядил примерно так: "City": ["Павловский Посад", "Орехово-зуево"]
        $this->min_price = min($this->Price); // Находим минимальную цену
        $this->max_price = max($this->Price); // Находим максимальную цену
        $this->min_total_space = min($this->total_space); // Находим минимальную общую площадь
        $this->max_total_space = max($this->total_space); // Находим максимальную общую площадь
        $this->min_floor = min($this->floor); // Находим минимальный этаж
        $this->max_floor = max($this->floor); // Находим максимальный этаж
    }

    /** Функция для вывода Выгодных предложений
     * здесь надо будет брать поле выгодных предложений
     * если она коненчо есть
     * и по этому свойству отдавать собственно объекты
    */
    public function GreatDeals() {
        // Делаем обращение в базу данных для получения нужнех нам данных
        $elements = \Bitrix\Iblock\Elements\ElementObjectsNewTable::getList([
            'select' => [
                'ID',
                'NAME',
                'Remote_photos_VALUE' => 'Remote_photos.IBLOCK_GENERIC_VALUE', // Получаем ID картинки которая лежит в свойстве
                'PRICE_VALUE' => 'Price.IBLOCK_GENERIC_VALUE', // Получаем цену
                'Location_VALUE' => 'Location.VALUE', // Получаем адрес
                'Agent_name_VALUE' => 'Agent_name.VALUE', // Получаем имя агента
                'Agent_tel_VALUE' => 'Agent_tel.VALUE', // Получаем получаем телефон агента
                'Spec_VALUE' => 'Spec.ITEM.VALUE', // Получаем свойство спецпредложения чтобы потом по этому свойству фильтровать элементы (это типо выгодные предложения)
            ],
            'filter' => [
                '=ACTIVE' => 'Y',
                '=Spec_VALUE' => $this->GreatDeals, // Фильтруем по свойству спецпредложение
            ],
        ])->fetchAll();

        // Делаем цикл по полученным элемента и заполняем массив
        foreach ($elements as $key => $element) {
            $this->checkDubli = false; // Сброс флага перед каждой итерацией
            // Делаем ещё 1 цикл чтобы пройтись по уже записанным данным и убрать дубли (ибо хз из-за чего, но данные которые приходят прямиком из бд, дублируются и мы можем получить 1 и тот же элемент раз 5)
            foreach ($this->ArrGreatDeals as $deal) {
                // Проверяем наличие дубля в общем массиве с текущей итерацией
                if ($deal['NAME'] == $element['NAME']) {
                    $this->checkDubli = true; // Если нашли, то присваиваем переменной true чтобы по этой переменной делать пропуск дубля
                    break;
                }
            }
            // Проверяем, что переменная не true, чтобы пропустить дубль
            if (!$this->checkDubli) {
                $this->ArrGreatDeals[$key]['PHOTO'] = \CFile::GetPath($element['Remote_photos_VALUE']); // Находим путь до картинки по её ID и записываем в общий массив
                $this->ArrGreatDeals[$key]['PRICE'] = $element['PRICE_VALUE']; // Записываем цену в общий массив
                $this->ArrGreatDeals[$key]['NAME'] = $element['NAME']; // Записываем название в общий массив
                $this->ArrGreatDeals[$key]['ADDRES'] = $element['Location_VALUE']; // Записываем адресс в общий массив
                $this->ArrGreatDeals[$key]['SHOW_PHONE_NUMBER']['Agent_name'] = $element['Agent_name_VALUE']; // Записываем имя агента в общий массив
                $this->ArrGreatDeals[$key]['SHOW_PHONE_NUMBER']['Agent_tel'] = $element['Agent_tel_VALUE']; // Записываем телефон агента в общий массив
                $this->ArrGreatDeals[$key]['GREAT_DEALS'] = $element['Spec_VALUE']; // Записываем значение свойства спецпредложения(выгодные предложения) в общий массив
            }
        }
        $this->ArrGreatDeals = array_values($this->ArrGreatDeals); // Перенумеровываем ключи массива чтобы он был нормально пронумерован
    }
}