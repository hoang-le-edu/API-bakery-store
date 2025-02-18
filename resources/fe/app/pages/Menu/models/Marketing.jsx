import React from 'react';
import '../Marketing.css';

const Marketing = ({ lang }) => {
    return (
        <div className="marketing flex flex-row justify-between items-center w-[90%] h-[200px] lg:w-[1100px] lg:h-[350px] m-auto mt-4 lg:mt-10">
            <div className="market_content flex flex-col justify-between h-full pt-5 pl-5 pb-5 lg:pt-10 lg:pl-20 lg:pb-10">
                {/*<button className="market_content_btn w-[130px] h-auto lg:w-[200px] lg:h-[50px] lg:ml-5">*/}
                {/*    <label className="btn_text text-sm lg:text-xl">{lang('LBL_LIMIT')}</label>*/}
                {/*</button>*/}
                {/*<label className="market_content_label text-sm lg:text-2xl lg:ml-5">{lang('LBL_GET_DISCOUNT')}</label>*/}
                {/*<div className="market_content_percent flex flex-row justify-start items-center lg:ml-20 ml-5">*/}
                {/*    <label className="percent_label text-4xl lg:text-8xl">40%</label>*/}
                {/*    <button className="percent_claim w-[60px] h-[20px] lg:w-[100px] lg:h-[50px] ml-5 lg:ml-10">*/}
                {/*        <label className="btn_text text-sm lg:text-xl">{lang('LBL_GET_CLAIM')}</label>*/}
                {/*    </button>*/}
                {/*</div>*/}
                {/*<div className="hidden md:block market_content_label text-sm lg:text-xl lg:ml-5">*/}
                {/*    {lang('LBL_DURATION_1')} {lang('LBL_DURATION_2')}*/}
                {/*</div>*/}
                {/*<div className="block lg:hidden market_content_label text-sm lg:text-2xl lg:ml-5">*/}
                {/*    {lang('LBL_DURATION_1')}*/}
                {/*</div>*/}
                {/*<div className="block lg:hidden market_content_label text-sm lg:text-2xl lg:ml-5">*/}
                {/*    {lang('LBL_DURATION_3')}*/}
                {/*</div>*/}
            </div>
            <div className="market_img">
                <img className="w-[160px] h-[140px] lg:w-[400px] lg:h-[350px]" src="@/assets/images/downloaded_images/counpon_img.png" alt="123" />
            </div>
        </div>
    );
};

export default Marketing;
