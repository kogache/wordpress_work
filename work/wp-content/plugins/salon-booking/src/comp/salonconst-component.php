<?php
class Response_Type {
	const JASON = 1;
	const HTML =2;
	const XML = 3;
	const JASON_406_RETURN = 4;
}

class Salon_Status {
	const OPEN = 0;
	const CLOSE = 1;
}

class Salon_YesNo {
	const Yes = 1;
	const No = 2;
}

class Salon_Reservation_Full_Empty {
	const LOW = 1;
	const MIDDLE = 2;
	const HIGH =  3;
}


class Salon_Reservation_Status {
	const COMPLETE = 1;
	const TEMPORARY = 2;
	const DELETED =  3;
	const INIT =  0;
	const DUMMY_RESERVED = 4;	//実績登録の場合のみ
	const SALES_REGISTERD =  10;
	const BEFORE_DELETED =  5;  //現状未使用
}

class Salon_Edit {
	const OK = 1;
	const NG = 0;
}

class Salon_Regist_Customer {
	const OK = 1;
	const NG = 0;
}

class Salon_Table_id {
	const RECORD = 1;
	const RESERVATION = 2;
}

class Salon_Category {
	const RADIO = 1;
	const CHECK_BOX = 2;
	const TEXT = 3;
	const SELECT = 4;
}

class Salon_Photo {
	const WIDTH = 450;
	const HEIGHT = 450;
	const RATIO = "80%";
}

class Salon_Config {
	const ONLY_BRANCH = 1;
	const MULTI_BRANCH = 2;
	const USER_LOGIN_OK = 1;
	const USER_LOGIN_NG = 0;
	const SET_STAFF_NORMAL = 1;
	const SET_STAFF_REVERSE = 2;
	const NO_PREFERNCE_OK = 1;
	const NO_PREFERNCE_NG = 0;
	const DEFALUT_BEFORE_DAY = 3;
	const DEFALUT_AFTER_DAY = 30;
	const DEFALUT_TIMELINE_Y_CNT = 5;   //	timelineのY軸に何人入れるか
	const DETAIL_MSG_OK = 1;
	const DETAIL_MSG_NG = 2;
	const NAME_ORDER_JAPAN = 1;
	const NAME_ORDER_OTHER = 2;
	const LOG_NEED =1;
	const LOG_NO_NEED =2;
	const DELETE_RECORD_YES = 1;
	const DELETE_RECORD_NO = 2;
	const DELETE_RECORD_PERIOD = 6;
	const MAINTENANCE_INCLUDE_STAFF = 0;
	const MAINTENANCE_NOT_INCLUDE_STAFF = 1;
	//mobile
	const MOBILE_NO_PHOTO = 1;
	const TAP_INTERVAL = 500;
	const MOBILE_USE_YES = 1;
	const MOBILE_USE_NO = 2;
	const PC_MOBILE_USE = 1;
	const PC_ONLY_USE = 2;
	const MOBILE_ONLY_USE = 3;

	//mobile
	const ALL_ITEMS_YES = 1;
	const ALL_ITEMS_NO = 2;
	const ALL_ITEMS_CHANGE_YES = 1;
	const ALL_ITEMS_CHANGE_NO = 2;
	//load tab
	const LOAD_STAFF = 1;
	const LOAD_MONTH = 2;
	const LOAD_WEEK = 3;
	const LOAD_DAY = 4;
	//
	const DEFALUT_RESERVE_DEADLINE = 30;
	const DEFALUT_RESERVE_DEADLINE_UNIT_DAY = 1;
	const DEFALUT_RESERVE_DEADLINE_UNIT_HOUR = 2;
	const DEFALUT_RESERVE_DEADLINE_UNIT_MIN = 3;
	//
	const NO_REGISTED_CUSTOMER_CD = -1;
	//
	const USE_SESSION = 1;
	const USE_NO_SESSION = 2;

	const SHOW_TAB = 1;
	const SHOW_NO_TAB = 2;

	const SETTING_PATERN_TIME = 1;
	const SETTING_PATERN_ORIGINAL = 2;

	const CONFIRM_NO = 1;
	const CONFIRM_BY_ADMIN = 2;
	const CONFIRM_BY_MAIL = 3;

	const COMMA = 1;
	const TAB = 2;

	const USE_SUBMENU = 1;
	const USE_NO_SUBMENU = 2;
}

class Salon_CRank {
	const STANDARD = 1;
	const SILVER = 2;
	const GOLD = 3;
	const PLATINUM = 4;
	const DIAMOND = 5;
}

class Salon_Coupon {
	const UNLIMITED = 1;
	const TIMES = 2;
	const RANK = 3;
	const FIRST = 4;
}

class Salon_Discount {
	const PERCENTAGE = 1;
	const AMOUNT = 2;
}

class Salon_Working {
	const USUALLY = 1;
	const DAY_OFF = 2;
// 	const IN = 3;
// 	const OUT = 4;
	const LATE_IN = 5;
	const EARLY_OUT = 6;
	const HOLIDAY_WORK = 7;
// 	const ABSENCE = 8;


}

class Salon_Position {
	const MAINTENANCE = 7;
}

class Salon_Color {
	const HOLIDAY = "#FFCCFF";
	const USUALLY = "#6699FF";
	//const PC_BACK = "#C2D5FC";
	const PC_BACK = "#FFFFFF";
	const PC_BACK_PALLET0 = "#696";
	const PC_BACK_PALLET1 = "#6633FF";
	const PC_BACK_PALLET2 = "#996600";
	const PC_BACK_PALLET3 = "#CCCC99";
	const PC_BACK_PALLET4 = "#F7F3F1";
	const PC_BACK_PALLET5 = "#C2D5FC";
	const PC_EVENT_BORDER = "#8894A3";

	const PC_EVENT = "#6BF2E5";
	const PC_EVENT_LINE = "#8A8A8A";
	const PC_BACK_SELCTED = "#D4FAE8";
	const PC_BACK_UNSELCTED = "#DEDEDE";

	//	const PC_HOLIDAY = "#F60151";
	const PC_HOLIDAY = "#FAC4BF";
	const PC_ONBUSINESS = "#696";

	const PC_FOCUS = "#D4FAE8";

	const PC_BACK_STAFF_PALLET1 = "#FF8EC6";
	const PC_BACK_STAFF_PALLET2 = "#FFC1FF";
	const PC_BACK_STAFF_PALLET3 = "#ADFFFF";
	const PC_BACK_STAFF_PALLET4 = "#ADFFAD";
	const PC_BACK_STAFF_PALLET5 = "#FFFFAD";
	const PC_BACK_STAFF_PALLET6 = "#FFD6AD";

}

class Salon_Default {
	const NO_PREFERENCE = -1;
	const ANYONE = -1;
	const BRANCH_CD = 1;
}

class Salon_Week {
	const SUNDAY = 0;
	const MONDAY = 1;

}

class Salon_Service {
	const HOTPEPPER = 1;
}

class Salon_Link_Status {
	const SALON_BEFORE_REGIST = 1;
	const SALON_AFTER_REGIST = 2;
	const SALON_BEFORE_UPDATE = 3;
	const SALON_AFTER_UPDATE = 4;
	const SALON_BEFORE_CANCEL = 5;
	const SALON_AFTER_CANCEL = 6;
	const SALON_LINK_ERROR = 11;

}
