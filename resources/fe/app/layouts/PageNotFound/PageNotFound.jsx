import React from "react";
import {Link} from "react-router-dom";
import pageNotFound from '../../assets/page_not_found.png';

const PageNotFound = () => {
    return (
        <section>
            <div className="container mx-auto">
                {/* navigation path info */}
                <div className="py-10 text-sm px-2">
                    <div>
                        <Link to="/">Home</Link> / Not Found
                    </div>
                </div>
                <div className="flex flex-col items-center mb-10">
                    <div>
                        <img className="h-[375px]" src={pageNotFound} alt=""/>
                    </div>
                    <div>
                        <button className="btn w-full bg-blue-400 hover:bg-primary">
                            <Link to="/">Back To Home</Link>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default PageNotFound;
