import { FormArray } from "@angular/forms";

export class Poll {
    id: number;
    admin_title:string;
    title: string;
    restrict_user:boolean;
    restrict_ip:boolean;
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