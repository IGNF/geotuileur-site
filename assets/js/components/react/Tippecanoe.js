import React from 'react' 
import { guid } from '../../utils.js';


export class TippeCanoe extends React.Component {
	constructor(props) {
		super(props);
        this._id = 'tippecanoe-' + guid();
	}

	render() {
		return (
            <div className={`o-teasers__item ${this.props.checked ? "active" : ""}`}>
                <article className='o-teaser o-teaser--hasImg' onClick={this.props.handler}>
                    <div className='o-teaser__inner'>
                        <div className='o-teaser__img'>
                            <img src={this.props.data.image_0} className={'half'}/>
                            <img src={this.props.data.image_1} className={'half'}/>
                            <img src={this.props.data.image}/>
                        </div>
                        <div className='o-teaser__content'>
                            <div className='custom-control custom-radio'>
                                <input 
                                    id={this._id} 
                                    className='custom-control-input' 
                                    type='radio'
                                    name={this.props.name}
                                    value={this.props.data.value} 
                                    checked={this.props.checked}
                                    onChange={this.props.handler} 
                                />
                                <label className='custom-control-label' htmlFor={this._id}>{this.props.data.label}</label>
                            </div>
                            <p>{this.props.data.explain}</p>
                        </div>
                    </div>
                </article>
            </div>
		);
	}
}


export class TippeCanoeList extends React.Component {
	constructor(props) {
		super(props);
        
        let entry = Object.entries(this.props).find(([key, tippecanoe]) => {
            return tippecanoe.default;   
        })
        
        this.state = { value: entry[1].value };
        this.handler = this.handler.bind(this);
	}
	
    handler(e) {
        let target = e.currentTarget;

        let value;
        if (target.tagName == 'ARTICLE') {
            value = e.currentTarget.querySelector('input').value;
        } else value = e.currentTarget.value
        this.setState({ value: value });
    }

    get value() {
        return this.state.value;
    }

	render() {
		return (
			<>
            { Object.entries(this.props).map(([key, tippecanoe]) => {
                return <TippeCanoe key={key} name='group' data={tippecanoe} handler={this.handler} checked={tippecanoe.value == this.state.value}/>
            }) }
			</>
		);
	}
}