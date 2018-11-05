import { FormArray } from "@angular/forms";

export class Poll {
    id: number;
    admin_title:string; 
    title: string;
    restrict_user:any;
    restrict_ip:any;
    restrict_ip_limit:number;
    active:boolean;
    start_date:string;
    end_date:string;
    locales:any;
    coins:any;
  localesFormgroup: any;
  coinsFormgroup: FormArray;

  }
  
  export class PollLocale
  {
    id:number;
    title:string;
    poll_id:number;
    description:string;
    locale_code:string;
  }
  export class PollCoin
  {
    id:number;
    short_title:string;
    market_cap:string;
    current_price:string;
    ordering:number;
    poll_id:number;
    file:any;
    icon:string;
  }
  export class CoinLocale
  {
    id:number;
    title:string;
    coin_id:number;
    description:string;
    locale_code:string;

  }